<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthorshipType;
use App\Enums\EnsembleType;
use App\Models\Ensemble;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Services\Concerns\CallsAnthropicApi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Researches one catalog song with Claude (web search + a structured submit tool) and records
 * an AI description, tags and per-part difficulty — the data the public song pages show.
 *
 * Unlike repertoire-search enrichment, this is targeted at a known song: Claude is told the
 * voicings directors actually performed so it describes that edition, and it may decline
 * (piece_identified = false) rather than guess.
 */
class SongEnrichmentService
{
    use CallsAnthropicApi;

    private const MAX_TAGS = 8;

    /**
     * Tags too generic to help a director filter; dropped even if the model returns them.
     */
    private const GENERIC_TAGS = ['concert', 'choral', 'choir', 'festival', 'high school', 'school', 'contemporary', 'music', 'repertoire', 'performance'];

    private string $apiKey;

    private string $apiVersion;

    private string $model;

    private int $maxWebSearches;

    public function __construct(private CatalogEnrichmentService $catalog)
    {
        $this->apiKey = (string) config('services.anthropic.api_key');
        $this->apiVersion = (string) config('services.anthropic.api_version');
        $this->model = (string) config('services.anthropic.repertoire_search_model');
        $this->maxWebSearches = min(4, (int) config('services.anthropic.repertoire_search_max_web_searches'));
    }

    /**
     * Which of the public-page fields this song still lacks.
     *
     * @return array{description: bool, tags: bool, difficulty: bool}
     */
    public function missingData(SongTitle $song): array
    {
        return [
            'description' => ! SongTitleDescription::query()->where('song_title_id', $song->id)->where('authorship_type', AuthorshipType::Ai->value)->exists(),
            'tags' => ! SongTitleTag::query()->where('song_title_id', $song->id)->whereIn('authorship_type', [AuthorshipType::Ai->value, AuthorshipType::Publisher->value])->exists(),
            'difficulty' => ! SongTitleDifficultyObservation::query()->where('song_title_id', $song->id)->exists(),
        ];
    }

    /**
     * @param  bool  $replaceExistingAi  replace earlier AI-written description/tags/difficulty instead of only filling gaps
     * @return array{identified: bool, recorded: list<string>, notes: string}
     */
    public function enrich(SongTitle $song, bool $replaceExistingAi = false): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured (ANTHROPIC_API_KEY).');
        }

        $song->loadMissing(['composer', 'arranger']);
        $voicings = $this->observedVoicings($song);

        $response = $this->postToAnthropic([
            'model' => $this->model,
            'max_tokens' => 4096,
            'tools' => $this->tools(),
            'tool_choice' => ['type' => 'auto'],
            'system' => $this->systemPrompt(),
            'messages' => [['role' => 'user', 'content' => $this->userMessage($song, $voicings)]],
        ], 'Song enrichment failed');

        $profile = $this->extractProfile($response);

        if (! ($profile['piece_identified'] ?? false)) {
            return ['identified' => false, 'recorded' => [], 'notes' => (string) ($profile['notes'] ?? '')];
        }

        $missing = $this->missingData($song);
        $recorded = [];

        DB::transaction(function () use ($song, $profile, $missing, $replaceExistingAi, &$recorded): void {
            if ($replaceExistingAi) {
                $this->deleteAiData($song);
                $missing = ['description' => true, 'tags' => true, 'difficulty' => true];
            }

            $description = trim((string) ($profile['song_description'] ?? ''));
            if ($missing['description'] && $description !== '') {
                $this->catalog->recordDescription($song, [
                    'song_description' => $description,
                    'description_source' => AuthorshipType::Ai->value,
                ], null, $this->model);
                $recorded[] = 'description';
            }

            $tags = $this->cleanTags($song, (array) ($profile['tags'] ?? []));
            if ($missing['tags'] && $tags !== []) {
                $this->catalog->recordTags($song, ['tags' => $tags], null);
                $recorded[] = 'tags';
            }

            $difficultyByPart = (array) ($profile['difficulty_by_part'] ?? []);
            if ($missing['difficulty'] && $this->hasRatedPart($difficultyByPart)) {
                $this->catalog->recordDifficultyObservations($song, [
                    'difficulty_by_part' => $difficultyByPart,
                    'difficulty_source' => $profile['difficulty_source'] ?? AuthorshipType::Ai->value,
                    'citation_urls' => $profile['citation_urls'] ?? [],
                ], null, $this->model);
                $recorded[] = 'difficulty';
            }
        });

        return ['identified' => true, 'recorded' => $recorded, 'notes' => (string) ($profile['notes'] ?? '')];
    }

    /**
     * @return list<string>
     */
    public function observedVoicings(SongTitle $song): array
    {
        return Ensemble::query()
            ->join('program_song_title', 'program_song_title.ensemble_id', '=', 'ensembles.id')
            ->where('program_song_title.song_title_id', $song->id)
            ->where('ensembles.type', '!=', EnsembleType::Unknown->value)
            ->distinct()
            ->pluck('ensembles.type')
            ->map(fn (mixed $type): ?string => ($type instanceof EnsembleType ? $type : EnsembleType::tryFrom((string) $type))?->label())
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Removes earlier machine-generated public data so a refresh replaces it. Rows written by a
     * director (authorship_id set) are always kept. Tags and difficulty labelled "publisher" were
     * still produced by the AI (it cited a publisher), so those are replaced too; the separate
     * publisher-sourced description is kept because public pages never show it.
     */
    private function deleteAiData(SongTitle $song): void
    {
        SongTitleDescription::query()->where('song_title_id', $song->id)->where('authorship_type', AuthorshipType::Ai->value)->whereNull('authorship_id')->delete();

        foreach ([SongTitleTag::class, SongTitleDifficultyObservation::class] as $modelClass) {
            $modelClass::query()
                ->where('song_title_id', $song->id)
                ->whereIn('authorship_type', [AuthorshipType::Ai->value, AuthorshipType::Publisher->value])
                ->whereNull('authorship_id')
                ->delete();
        }
    }

    /**
     * Lower-cases, de-duplicates and caps tags, dropping the composer's or arranger's name.
     *
     * @param  array<int, mixed>  $tags
     * @return list<string>
     */
    private function cleanTags(SongTitle $song, array $tags): array
    {
        $names = collect([$song->composer?->artist_name, $song->arranger?->artist_name, $song->composer?->artist_last_name, $song->arranger?->artist_last_name])
            ->filter()
            ->map(fn (string $name): string => mb_strtolower($name))
            ->all();

        return collect($tags)
            ->filter(fn (mixed $tag): bool => is_string($tag))
            ->map(fn (string $tag): string => mb_strtolower(trim($tag)))
            ->filter(fn (string $tag): bool => $tag !== '' && mb_strlen($tag) <= 40 && ! in_array($tag, $names, true) && ! in_array($tag, self::GENERIC_TAGS, true))
            ->unique()
            ->take(self::MAX_TAGS)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $difficultyByPart
     */
    private function hasRatedPart(array $difficultyByPart): bool
    {
        return collect($difficultyByPart)->contains(fn (mixed $rating): bool => in_array($rating, ['easy', 'moderate', 'challenging'], true));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function extractProfile(array $response): array
    {
        if (($response['stop_reason'] ?? null) === 'refusal') {
            throw new RuntimeException('The AI declined to research this song.');
        }

        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === 'submit_song_profile') {
                return (array) $block['input'];
            }
        }

        throw new RuntimeException('The AI did not return a song profile (stop reason: '.($response['stop_reason'] ?? 'unknown').').');
    }

    /**
     * @param  list<string>  $voicings
     */
    private function userMessage(SongTitle $song, array $voicings): string
    {
        $lines = [
            'Title: '.$song->song_title,
            'Composer: '.($song->composer->artist_name ?? 'unknown'),
            'Arranger: '.($song->arranger->artist_name ?? 'none listed'),
            'Voicings school choirs used when performing it (from their concert programs): '.($voicings !== [] ? implode(', ', $voicings) : 'not recorded'),
        ];

        return "Research this piece from the ChoirTrends catalog, then call submit_song_profile.\n\n".implode("\n", $lines);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You research choral pieces for ChoirTrends, a site where school choral directors share concert
programs. Your profile of a piece is published on a public page about it, so accuracy matters more
than completeness: an empty field is fine, a wrong one is not.

You have two tools:
- web_search: confirm which piece this is, its published voicing(s) and accompaniment, and any
  publisher grade or level. Prefer publisher and retailer pages (e.g. jwpepper.com, halleonard.com,
  the composer's own site) and reputable reviews.
- submit_song_profile: call it exactly once, as your last action.

Guidelines:
- First make sure you know which piece this is (title + composer, and the arranger's edition when
  one is listed). If you cannot identify it with confidence, set piece_identified to false, leave
  the other fields empty or null, and say why in notes.
- Many pieces are published in several voicings. Describe the version these school choirs sang:
  you are told the voicings recorded in their programs. Never describe the piece as a voicing that
  contradicts that information; if it was not recorded, do not state a voicing at all.
- song_description: 1-3 sentences in your own words (never copy publisher marketing copy) about the
  text or theme, mood, and notable musical features. Neutral and factual; no praise, no
  recommendations.
- tags: up to 8 short lowercase descriptors a director would filter by — mood, occasion or season,
  sacred/secular, language, accompaniment (e.g. "a cappella", "piano"), style. Do not include the
  composer's or arranger's name, do not include voicing tags, and skip generic words that fit almost
  any piece ("concert", "choral", "festival", "high school", "contemporary" on its own).
- difficulty_by_part: rate each part easy / moderate / challenging only when you can defend it from
  the piece's ranges and writing or from a published grade; otherwise "unknown". Use "n/a" for a
  part the piece does not have. Set difficulty_source to "publisher" only when you found and cite a
  published grade or level; otherwise "ai"; null only if every part is unknown or n/a.
PROMPT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        $rating = ['type' => 'string', 'enum' => ['easy', 'moderate', 'challenging', 'n/a', 'unknown']];

        return [
            [
                'type' => 'web_search_20250305',
                'name' => 'web_search',
                'max_uses' => $this->maxWebSearches,
            ],
            [
                'name' => 'submit_song_profile',
                'description' => 'Submit the researched profile of the piece. Call exactly once, as your last action.',
                'input_schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['piece_identified', 'song_description', 'tags', 'difficulty_by_part', 'difficulty_source', 'citation_urls', 'notes'],
                    'properties' => [
                        'piece_identified' => ['type' => 'boolean', 'description' => 'False when you cannot confidently identify this specific piece.'],
                        'song_description' => ['type' => ['string', 'null'], 'maxLength' => 500],
                        'tags' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => self::MAX_TAGS],
                        'difficulty_by_part' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['soprano', 'alto', 'tenor', 'bass'],
                            'properties' => ['soprano' => $rating, 'alto' => $rating, 'tenor' => $rating, 'bass' => $rating],
                        ],
                        'difficulty_source' => ['type' => ['string', 'null'], 'enum' => ['ai', 'publisher', null]],
                        'citation_urls' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 3],
                        'notes' => ['type' => 'string', 'maxLength' => 300, 'description' => 'Brief note on which edition you profiled, or why you could not identify the piece.'],
                    ],
                ],
            ],
        ];
    }
}
