<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\SongTitleOrigin;
use App\Models\Artist;
use App\Models\RepertoireQuery;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogEnrichmentService
{
    /**
     * Turn a completed repertoire search's results into lasting catalog data:
     * new songs Claude found that aren't in ChoirTrends yet, and attributed
     * difficulty/tag/description observations for every result (new or existing).
     *
     * Exceptions are allowed to propagate — this runs inside its own queued job
     * (EnrichCatalogFromRepertoireSearch), which is responsible for retry/failure
     * handling in isolation from the user-facing search.
     */
    public function enrich(RepertoireQuery $repertoireQuery): void
    {
        /** @var list<array<string, mixed>> $results */
        $results = $repertoireQuery->response['results'] ?? [];

        DB::transaction(function () use ($results, $repertoireQuery): void {
            $artistNameParser = new ArtistNameParser;

            foreach ($results as $result) {
                $songTitle = $this->resolveOrCreateSongTitle($result, $artistNameParser);

                if ($songTitle === null) {
                    continue;
                }

                $this->recordDifficultyObservations($songTitle, $result, $repertoireQuery);
                $this->recordTags($songTitle, $result, $repertoireQuery);
                $this->recordDescription($songTitle, $result, $repertoireQuery);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function resolveOrCreateSongTitle(array $result, ArtistNameParser $artistNameParser): ?SongTitle
    {
        if (empty($result['song_title'])) {
            return null;
        }

        if (($result['matched_song_title_id'] ?? null) !== null) {
            $songTitle = SongTitle::find($result['matched_song_title_id']);

            if ($songTitle !== null) {
                return $songTitle;
            }
        }

        $composerId = null;
        $arrangerId = null;

        if (! empty($result['composer'])) {
            $composerData = $artistNameParser->parse($result['composer']);
            $composer = Artist::firstOrCreate(['artist_name' => $composerData['artist_name']], $composerData);
            $composerId = $composer->id;
        }

        if (! empty($result['arranger'])) {
            $arrangerData = $artistNameParser->parse($result['arranger']);
            $arranger = Artist::firstOrCreate(['artist_name' => $arrangerData['artist_name']], $arrangerData);
            $arrangerId = $arranger->id;
        }

        return SongTitle::firstOrCreate(
            [
                'song_title' => $result['song_title'],
                'composer_id' => $composerId,
                'arranger_id' => $arrangerId,
            ],
            ['origin' => SongTitleOrigin::AiDiscovered->value]
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function recordDifficultyObservations(SongTitle $songTitle, array $result, RepertoireQuery $repertoireQuery): void
    {
        $authorshipType = $result['difficulty_source'] ?? AuthorshipType::Ai->value;
        $citationUrl = $result['citation_urls'][0] ?? null;

        foreach ($result['difficulty_by_part'] ?? [] as $voicePart => $rating) {
            if (in_array($rating, ['n/a', 'unknown'], true)) {
                continue;
            }

            $difficultyLevel = DifficultyLevel::from($rating);

            SongTitleDifficultyObservation::create([
                'song_title_id' => $songTitle->id,
                'voice_part' => $voicePart,
                'difficulty_label' => $difficultyLevel->value,
                'difficulty_value' => $difficultyLevel->numericValue(),
                'authorship_type' => $authorshipType,
                'authorship_id' => null,
                'repertoire_query_id' => $repertoireQuery->id,
                'citation_url' => $citationUrl,
                'model_version' => config('services.anthropic.repertoire_search_model'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function recordTags(SongTitle $songTitle, array $result, RepertoireQuery $repertoireQuery): void
    {
        foreach ($result['tags'] ?? [] as $tag) {
            $normalizedTag = Str::of($tag)->trim()->lower()->value();

            if ($normalizedTag === '') {
                continue;
            }

            $exists = SongTitleTag::query()
                ->where('song_title_id', $songTitle->id)
                ->where('tag', $normalizedTag)
                ->where('authorship_type', AuthorshipType::Ai->value)
                ->whereNull('authorship_id')
                ->exists();

            if ($exists) {
                continue;
            }

            SongTitleTag::create([
                'song_title_id' => $songTitle->id,
                'tag' => $normalizedTag,
                'authorship_type' => AuthorshipType::Ai->value,
                'authorship_id' => null,
                'repertoire_query_id' => $repertoireQuery->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function recordDescription(SongTitle $songTitle, array $result, RepertoireQuery $repertoireQuery): void
    {
        if (empty($result['song_description'])) {
            return;
        }

        $authorshipType = $result['description_source'] ?? AuthorshipType::Ai->value;

        SongTitleDescription::query()
            ->where('song_title_id', $songTitle->id)
            ->where('authorship_type', $authorshipType)
            ->whereNull('authorship_id')
            ->first()
            ?->update([
                'description' => $result['song_description'],
                'repertoire_query_id' => $repertoireQuery->id,
                'model_version' => config('services.anthropic.repertoire_search_model'),
            ])
            ?? SongTitleDescription::create([
                'song_title_id' => $songTitle->id,
                'description' => $result['song_description'],
                'authorship_type' => $authorshipType,
                'authorship_id' => null,
                'repertoire_query_id' => $repertoireQuery->id,
                'model_version' => config('services.anthropic.repertoire_search_model'),
            ]);
    }
}
