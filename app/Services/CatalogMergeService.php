<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Artist;
use App\Models\ArtistRepresentation;
use App\Models\DigitalProgramSongSetting;
use App\Models\SongTitle;
use App\Models\SongTitleAssessment;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Models\UserSongFile;
use App\Models\UserSongLyrics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Merges duplicate artists and song titles without losing anything attached to them.
 *
 * Deleting a song title cascades to its tags, descriptions, difficulty observations,
 * assessments, digital program settings and directors' lyrics and files, so every
 * child row is moved to the keeper first; where a unique constraint means the keeper
 * already has an equivalent row, the keeper's row wins.
 */
class CatalogMergeService
{
    /**
     * @return array{songs_reassigned: int, songs_merged: int, representations_moved: int}
     */
    public function mergeArtist(Artist $keeper, Artist $duplicate): array
    {
        if ($keeper->is($duplicate)) {
            return ['songs_reassigned' => 0, 'songs_merged' => 0, 'representations_moved' => 0];
        }

        return DB::transaction(function () use ($keeper, $duplicate): array {
            $report = ['songs_reassigned' => 0, 'songs_merged' => 0, 'representations_moved' => 0];

            $songs = SongTitle::query()
                ->where('composer_id', $duplicate->id)
                ->orWhere('arranger_id', $duplicate->id)
                ->get();

            foreach ($songs as $song) {
                if (! SongTitle::query()->whereKey($song->id)->exists()) {
                    continue;
                }

                $composerId = $this->remapId($song->composer_id, $duplicate->id, $keeper->id);
                $arrangerId = $this->remapId($song->arranger_id, $duplicate->id, $keeper->id);

                $existing = SongTitle::query()
                    ->whereKeyNot($song->id)
                    ->where('song_title', $song->song_title)
                    ->when($composerId === null, fn ($query) => $query->whereNull('composer_id'), fn ($query) => $query->where('composer_id', $composerId))
                    ->when($arrangerId === null, fn ($query) => $query->whereNull('arranger_id'), fn ($query) => $query->where('arranger_id', $arrangerId))
                    ->first();

                if ($existing) {
                    $this->mergeSongTitle($existing, $song);
                    $report['songs_merged']++;

                    continue;
                }

                SongTitle::query()->whereKey($song->id)->update(['composer_id' => $composerId, 'arranger_id' => $arrangerId]);
                $report['songs_reassigned']++;
            }

            foreach (ArtistRepresentation::query()->where('artist_id', $duplicate->id)->get() as $representation) {
                $keeperHasEquivalent = ArtistRepresentation::query()
                    ->where('artist_id', $keeper->id)
                    ->where('representation_category_id', $representation->getAttribute('representation_category_id'))
                    ->where('source_url', $representation->getAttribute('source_url'))
                    ->exists();

                if ($keeperHasEquivalent) {
                    $representation->delete();

                    continue;
                }

                ArtistRepresentation::query()->whereKey($representation->getKey())->update(['artist_id' => $keeper->id]);
                $report['representations_moved']++;
            }

            $duplicate->delete();

            return $report;
        });
    }

    /**
     * @return array{performances_moved: int}
     */
    public function mergeSongTitle(SongTitle $keeper, SongTitle $duplicate): array
    {
        if ($keeper->is($duplicate)) {
            return ['performances_moved' => 0];
        }

        return DB::transaction(function () use ($keeper, $duplicate): array {
            $performancesMoved = $this->moveUniqueRows('program_song_title', 'program_id', $keeper->id, $duplicate->id);

            $this->moveModelRows(DigitalProgramSongSetting::class, ['digital_program_id'], $keeper->id, $duplicate->id);
            $this->moveModelRows(SongTitleAssessment::class, ['grade_level_context'], $keeper->id, $duplicate->id);
            $this->moveModelRows(UserSongLyrics::class, ['user_id'], $keeper->id, $duplicate->id);
            $this->moveModelRows(SongTitleTag::class, ['tag', 'authorship_type', 'authorship_id'], $keeper->id, $duplicate->id);

            foreach ([SongTitleDescription::class, SongTitleDifficultyObservation::class, UserSongFile::class] as $modelClass) {
                $modelClass::query()->where('song_title_id', $duplicate->id)->update(['song_title_id' => $keeper->id]);
            }

            $duplicate->delete();

            return ['performances_moved' => $performancesMoved];
        });
    }

    private function remapId(mixed $currentId, int $fromId, int $toId): ?int
    {
        if ($currentId === null) {
            return null;
        }

        return (int) $currentId === $fromId ? $toId : (int) $currentId;
    }

    /**
     * Moves pivot rows (no model) to the keeper unless the keeper already has the same partner id.
     */
    private function moveUniqueRows(string $table, string $partnerColumn, int $keeperId, int $duplicateId): int
    {
        $keeperPartnerIds = DB::table($table)->where('song_title_id', $keeperId)->pluck($partnerColumn)->all();

        DB::table($table)
            ->where('song_title_id', $duplicateId)
            ->whereIn($partnerColumn, $keeperPartnerIds)
            ->delete();

        return DB::table($table)
            ->where('song_title_id', $duplicateId)
            ->update(['song_title_id' => $keeperId]);
    }

    /**
     * Moves a child model's rows to the keeper, dropping any row the keeper already has
     * an equivalent of (matched on $matchColumns).
     *
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $matchColumns
     */
    private function moveModelRows(string $modelClass, array $matchColumns, int $keeperId, int $duplicateId): void
    {
        $keeperSignatures = $modelClass::query()
            ->where('song_title_id', $keeperId)
            ->get()
            ->map(fn ($row): string => $this->signature($row->getAttributes(), $matchColumns))
            ->flip();

        foreach ($modelClass::query()->where('song_title_id', $duplicateId)->get() as $row) {
            if ($keeperSignatures->has($this->signature($row->getAttributes(), $matchColumns))) {
                $row->delete();

                continue;
            }

            $modelClass::query()->whereKey($row->getKey())->update(['song_title_id' => $keeperId]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $columns
     */
    private function signature(array $attributes, array $columns): string
    {
        return json_encode(array_map(
            fn (string $column): string => mb_strtolower(trim((string) ($attributes[$column] ?? ''))),
            $columns,
        ), JSON_THROW_ON_ERROR);
    }
}
