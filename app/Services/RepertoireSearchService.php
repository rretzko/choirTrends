<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RepertoireQuerySource;
use App\Models\RepertoireQuery;
use App\Models\SongTitle;
use App\Models\SongTitleAssessment;
use App\Models\User;
use App\Services\Concerns\CallsAnthropicApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RepertoireSearchService
{
    use CallsAnthropicApi;

    private const CANDIDATE_POOL_LIMIT = 100;

    private const MAX_RESULTS = 8;

    private const ASSESSMENT_CONTEXT = 'general';

    private string $apiKey;

    private string $apiVersion;

    private string $model;

    private int $maxWebSearches;

    public function __construct()
    {
        $this->apiKey = (string) config('services.anthropic.api_key');
        $this->apiVersion = config('services.anthropic.api_version');
        $this->model = config('services.anthropic.repertoire_search_model');
        $this->maxWebSearches = (int) config('services.anthropic.repertoire_search_max_web_searches');

        if (empty($this->apiKey)) {
            throw new \Exception('Anthropic API key is not configured. Please set ANTHROPIC_API_KEY in your .env file.');
        }
    }

    /**
     * Create the query record immediately (before the job runs) so it counts toward
     * a guest's throttle right away — the AI call can take 10-30s, and without an
     * eagerly-created row a guest could fire far more than their allotted queries
     * before the first one finishes and gets persisted.
     */
    public function createPendingQuery(
        string $queryText,
        RepertoireQuerySource $source,
        ?User $user = null,
        ?string $ipAddress = null
    ): RepertoireQuery {
        return RepertoireQuery::create([
            'user_id' => $user?->id,
            'ip_address' => $ipAddress,
            'source' => $source,
            'query_text' => $queryText,
        ]);
    }

    public function process(RepertoireQuery $repertoireQuery, bool $restrictToOwnCatalog = false): RepertoireQuery
    {
        try {
            $candidates = $this->buildCandidatePool($restrictToOwnCatalog ? $repertoireQuery->user_id : null);

            $response = $this->postToAnthropic([
                'model' => $this->model,
                'max_tokens' => 4096,
                'temperature' => 0.3,
                'tools' => $this->buildTools(),
                'tool_choice' => ['type' => 'auto'],
                'system' => $this->buildSystemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildUserMessage($repertoireQuery->query_text, $candidates, $repertoireQuery->user_id === null),
                    ],
                ],
            ], 'Repertoire search failed');

            $payload = $this->extractResultPayload($response);
            $payload = $this->validateAndNormalize($payload, $candidates->pluck('id'));

            $this->cacheAssessments($payload['results']);

            $repertoireQuery->update(['response' => $payload]);

            return $repertoireQuery;
        } catch (\Exception $e) {
            Log::error('Repertoire search failed', [
                'query' => $repertoireQuery->query_text,
                'error' => $e->getMessage(),
            ]);

            $repertoireQuery->update(['error' => $e->getMessage()]);

            return $repertoireQuery;
        }
    }

    private function buildCandidatePool(?int $restrictToUserId = null): Collection
    {
        $songs = SongTitle::query()
            ->withCount('programs as performed_count')
            ->with(['composer', 'arranger'])
            ->whereHas('programs', function ($q) use ($restrictToUserId) {
                if ($restrictToUserId !== null) {
                    $q->where('user_id', $restrictToUserId);
                }
            })
            ->orderByDesc('performed_count')
            ->limit(self::CANDIDATE_POOL_LIMIT)
            ->get();

        $assessments = SongTitleAssessment::query()
            ->whereIn('song_title_id', $songs->pluck('id'))
            ->where('grade_level_context', self::ASSESSMENT_CONTEXT)
            ->get()
            ->keyBy('song_title_id');

        return $songs->map(function (SongTitle $song) use ($assessments) {
            /** @var SongTitleAssessment|null $assessment */
            $assessment = $assessments->get($song->id);

            return [
                'id' => $song->id,
                'song_title' => $song->song_title,
                'composer' => $song->composer?->artist_name,
                'arranger' => $song->arranger?->artist_name,
                'performed_count' => (int) $song->performed_count,
                'known_assessment' => $assessment ? [
                    'voicing' => $assessment->voicing,
                    'difficulty_by_part' => $assessment->difficulty_by_part,
                    'youtube_url' => $assessment->youtube_url,
                ] : null,
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTools(): array
    {
        return [
            [
                'type' => 'web_search_20250305',
                'name' => 'web_search',
                'max_uses' => $this->maxWebSearches,
            ],
            [
                'name' => 'submit_repertoire_results',
                'description' => 'Submit the final ranked repertoire recommendations for the director\'s query. Call this exactly once, after any web searches, as your last action.',
                'input_schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['query_interpretation', 'results'],
                    'properties' => [
                        'query_interpretation' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['voicing', 'school_level', 'primary_ask', 'interpretation_notes'],
                            'properties' => [
                                'voicing' => [
                                    'type' => ['string', 'null'],
                                    'enum' => ['SATB', 'SSA', 'SSAA', 'TTBB', 'TB', 'unison', 'treble', 'mixed', null],
                                ],
                                'school_level' => [
                                    'type' => ['string', 'null'],
                                    'enum' => ['elementary', 'middle_school', 'high_school', 'college', 'community', null],
                                ],
                                'primary_ask' => [
                                    'type' => 'string',
                                    'enum' => ['difficulty_balance', 'theme_or_occasion', 'mood_or_style', 'programming_general', 'other'],
                                ],
                                'interpretation_notes' => [
                                    'type' => 'string',
                                    'maxLength' => 200,
                                    'description' => 'Plain-language paraphrase of the whole ask — theme, mood, occasion, difficulty, or a mix. Not difficulty-specific.',
                                ],
                            ],
                        ],
                        'results' => [
                            'type' => 'array',
                            'minItems' => 0,
                            'maxItems' => self::MAX_RESULTS,
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => [
                                    'song_title', 'composer', 'voicing', 'source', 'matched_song_title_id',
                                    'difficulty_by_part', 'fit_rationale', 'youtube_url', 'youtube_confidence',
                                ],
                                'properties' => [
                                    'song_title' => ['type' => 'string'],
                                    'composer' => ['type' => ['string', 'null']],
                                    'arranger' => ['type' => ['string', 'null']],
                                    'voicing' => [
                                        'type' => ['string', 'null'],
                                        'description' => 'This specific piece\'s actual voicing, e.g. SATB, SSA, TTBB — not the director\'s query voicing.',
                                    ],
                                    'source' => [
                                        'type' => 'string',
                                        'enum' => ['internal_catalog', 'web_knowledge'],
                                    ],
                                    'matched_song_title_id' => ['type' => ['integer', 'null']],
                                    'difficulty_by_part' => [
                                        'type' => 'object',
                                        'additionalProperties' => false,
                                        'required' => ['soprano', 'alto', 'tenor', 'bass'],
                                        'properties' => [
                                            'soprano' => ['type' => 'string', 'enum' => ['easy', 'moderate', 'challenging', 'n/a', 'unknown']],
                                            'alto' => ['type' => 'string', 'enum' => ['easy', 'moderate', 'challenging', 'n/a', 'unknown']],
                                            'tenor' => ['type' => 'string', 'enum' => ['easy', 'moderate', 'challenging', 'n/a', 'unknown']],
                                            'bass' => ['type' => 'string', 'enum' => ['easy', 'moderate', 'challenging', 'n/a', 'unknown']],
                                        ],
                                    ],
                                    'fit_rationale' => ['type' => 'string', 'maxLength' => 240],
                                    'youtube_url' => ['type' => ['string', 'null']],
                                    'youtube_confidence' => [
                                        'type' => ['string', 'null'],
                                        'enum' => ['found_via_search', 'unverified', null],
                                    ],
                                    'citation_urls' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'maxItems' => 3,
                                    ],
                                    'tags' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'maxItems' => 6,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are the repertoire-matching assistant for ChoirTrends, a platform where choral directors
share and browse real concert programs. A director has asked a natural-language question about
repertoire — often about difficulty balance between voice parts, mood, occasion, or theme.

You have two tools:
- web_search: use it to verify how difficult a piece is for specific voice parts, confirm
  voicing (SATB/SSA/TTBB/etc.), and find an official or high-quality YouTube performance/demo
  recording. Search publisher pages (e.g. jwpepper.com, halleonard.com), reviews, and forums —
  not just the song title alone.
- submit_repertoire_results: call this exactly once, as your last action, with your final answer.

You will also be given a list of candidate songs already in the ChoirTrends catalog (titles real
directors have performed), each with how many programs it appears in. Prefer these when they
genuinely fit the query — they're relevant because peers are actually singing them — but do not
force a bad fit just to use the internal list. You may also recommend well-known repertoire from
your own knowledge or web search that isn't in the catalog yet; mark those "web_knowledge".

Guidelines:
- Return at most 8 results, ranked best-fit first.
- Every difficulty judgment must be something you can defend from either the piece's known
  vocal ranges/tessitura or a source you found — don't guess if you have no basis.
- Use "unknown" rather than fabricating a difficulty rating.
- Only spend a web_search call verifying voice-part difficulty when the query concerns
  difficulty or part balance. For theme/occasion/mood queries, leave difficulty_by_part as
  "unknown" for all parts unless you already know it confidently — don't burn search budget
  on a dimension nobody asked about.
- Only include a youtube_url if you found one via web_search that plausibly matches this
  specific piece and (where relevant) arrangement.
- fit_rationale should speak directly to what the director asked for, not a generic description
  of the piece.
- Populate tags with whatever short descriptors are actually relevant to this query (mood,
  occasion/season, sacred/secular, language, accompaniment). Leave it empty if nothing
  meaningful applies — don't force generic tags.
PROMPT;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $candidates
     */
    private function buildUserMessage(string $query, Collection $candidates, bool $isGuest): string
    {
        $context = $isGuest ? 'This visitor is not logged in.' : 'This is a registered ChoirTrends director.';
        $candidatesJson = json_encode($candidates->values()->all());

        return <<<MESSAGE
Director's query: "{$query}"

Context: {$context}

Candidate songs already in the ChoirTrends catalog (JSON — id/song_title/composer/arranger/performed_count/known_assessment):
{$candidatesJson}

Search the web as needed, then call submit_repertoire_results with your final answer.
MESSAGE;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function extractResultPayload(array $response): array
    {
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === 'submit_repertoire_results') {
                return $block['input'];
            }
        }

        throw new \Exception('The AI did not return structured repertoire results. Please try rephrasing your search.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, int>  $candidateIds
     * @return array<string, mixed>
     */
    private function validateAndNormalize(array $payload, Collection $candidateIds): array
    {
        $payload['results'] = collect($payload['results'] ?? [])
            ->map(function (array $result) use ($candidateIds) {
                if ($result['matched_song_title_id'] !== null && ! $candidateIds->contains($result['matched_song_title_id'])) {
                    $result['matched_song_title_id'] = null;
                    $result['source'] = 'web_knowledge';
                }

                if ($result['youtube_url'] && ! preg_match('#^https://(www\.)?(youtube\.com|youtu\.be)/#', $result['youtube_url'])) {
                    $result['youtube_url'] = null;
                    $result['youtube_confidence'] = null;
                }

                $result['citation_urls'] ??= [];
                $result['tags'] ??= [];

                return $result;
            })
            ->values()
            ->all();

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function cacheAssessments(array $results): void
    {
        foreach ($results as $result) {
            if ($result['matched_song_title_id'] === null) {
                continue;
            }

            SongTitleAssessment::updateOrCreate(
                [
                    'song_title_id' => $result['matched_song_title_id'],
                    'grade_level_context' => self::ASSESSMENT_CONTEXT,
                ],
                [
                    'voicing' => $result['voicing'] ?? null,
                    'difficulty_by_part' => $result['difficulty_by_part'],
                    'youtube_url' => $result['youtube_url'],
                    'youtube_confidence' => $result['youtube_confidence'],
                    'citation_urls' => $result['citation_urls'],
                    'model_version' => $this->model,
                    'assessed_at' => now(),
                ]
            );
        }
    }
}
