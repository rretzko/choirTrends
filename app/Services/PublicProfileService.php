<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\EnsembleType;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleTag;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data for the public composer and song pages. Built on PublicTrendsService so every
 * piece or artist shown has itself met the public school threshold.
 */
class PublicProfileService
{
    private const RELATED_LIMIT = 6;

    private const TAG_LIMIT = 12;

    public function __construct(private PublicTrendsService $trends) {}

    /**
     * @return array{
     *     schoolCount: int,
     *     pieceCount: int,
     *     performanceCount: int,
     *     isComposer: bool,
     *     isArranger: bool,
     *     composedPieces: Collection<int, TrendingPiece>,
     *     arrangedPieces: Collection<int, TrendingPiece>,
     *     unlistedPieceCount: int,
     *     performancesBySchoolYear: array<string, int>,
     *     relatedArtists: Collection<int, TrendingArtist>
     * }
     */
    public function artistProfile(Artist $artist): array
    {
        return $this->trends->remember("artist_profile:{$artist->id}", function () use ($artist): array {
            $byArtist = fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('song_titles.composer_id', $artist->id)
                ->orWhere('song_titles.arranger_id', $artist->id));

            $totals = $this->performanceTotals($byArtist);

            $composedPieces = $this->trends->publicPieces(fn (Builder $query) => $query->where('song_titles.composer_id', $artist->id));
            $arrangedPieces = $this->trends->publicPieces(fn (Builder $query) => $query->where('song_titles.arranger_id', $artist->id));
            $listedPieceCount = $composedPieces->merge($arrangedPieces)->unique('id')->count();

            return [
                'schoolCount' => $totals['schools'],
                'pieceCount' => $totals['pieces'],
                'performanceCount' => $totals['performances'],
                'isComposer' => SongTitle::query()->where('composer_id', $artist->id)->has('programs')->exists(),
                'isArranger' => SongTitle::query()->where('arranger_id', $artist->id)->has('programs')->exists(),
                'composedPieces' => $composedPieces,
                'arrangedPieces' => $arrangedPieces,
                'unlistedPieceCount' => max(0, $totals['pieces'] - $listedPieceCount),
                'performancesBySchoolYear' => $this->performancesBySchoolYear($byArtist),
                'relatedArtists' => $this->relatedArtists($artist, $byArtist),
            ];
        });
    }

    /**
     * @return array{
     *     piece: TrendingPiece,
     *     voicings: list<string>,
     *     difficulty: string|null,
     *     tags: list<string>,
     *     summary: string|null,
     *     performancesBySchoolYear: array<string, int>,
     *     alsoProgrammed: Collection<int, TrendingPiece>
     * }|null
     */
    public function songProfile(SongTitle $song): ?array
    {
        return $this->trends->remember("song_profile:{$song->id}", function () use ($song): ?array {
            $piece = $this->trends->publicPieces(fn (Builder $query) => $query->where('song_titles.id', $song->id))->first();

            if ($piece === null) {
                return null;
            }

            $bySong = fn (Builder $query) => $query->where('song_titles.id', $song->id);
            $overallDifficulty = $song->overallDifficulty();

            return [
                'piece' => $piece,
                'voicings' => $this->voicings($song),
                'difficulty' => $overallDifficulty === null
                    ? null
                    : DifficultyLevel::fromNumericValue((int) round($overallDifficulty))->label(),
                'tags' => $this->tags($song),
                'summary' => $this->aiSummary($song),
                'performancesBySchoolYear' => $this->performancesBySchoolYear($bySong),
                'alsoProgrammed' => $this->alsoProgrammed($song),
            ];
        });
    }

    /**
     * School year label for a concert date; the school year runs September to July.
     */
    public static function schoolYearLabel(CarbonInterface $date): string
    {
        $startYear = $date->month >= 9 ? $date->year : $date->year - 1;

        return $startYear.'–'.substr((string) ($startYear + 1), -2);
    }

    /**
     * @param  callable(Builder<SongTitle>): mixed  $constraint
     * @return array{schools: int, pieces: int, performances: int}
     */
    private function performanceTotals(callable $constraint): array
    {
        $row = $this->performancesQuery($constraint)
            ->selectRaw('COUNT(DISTINCT programs.school_id) as school_count')
            ->selectRaw('COUNT(DISTINCT song_titles.id) as piece_count')
            ->selectRaw('COUNT(*) as performance_count')
            ->toBase()
            ->first();

        return [
            'schools' => (int) ($row->school_count ?? 0),
            'pieces' => (int) ($row->piece_count ?? 0),
            'performances' => (int) ($row->performance_count ?? 0),
        ];
    }

    /**
     * @param  callable(Builder<SongTitle>): mixed  $constraint
     * @return array<string, int>
     */
    private function performancesBySchoolYear(callable $constraint): array
    {
        return $this->performancesQuery($constraint)
            ->pluck('programs.event_date')
            ->filter()
            ->map(fn (mixed $date): string => self::schoolYearLabel(Carbon::parse((string) $date)))
            ->countBy()
            ->sortKeysDesc()
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * Other public composers whose pieces appear in the same programs, most frequent first.
     *
     * @param  callable(Builder<SongTitle>): mixed  $byArtist
     * @return Collection<int, TrendingArtist>
     */
    private function relatedArtists(Artist $artist, callable $byArtist): Collection
    {
        $programIds = $this->performancesQuery($byArtist)->select('programs.id');

        $publicArtists = $this->trends->allPublicArtists()->keyBy('id');

        return SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->whereIn('program_song_title.program_id', $programIds)
            ->whereNotNull('song_titles.composer_id')
            ->where('song_titles.composer_id', '!=', $artist->id)
            ->groupBy('song_titles.composer_id')
            ->orderByRaw('COUNT(DISTINCT program_song_title.program_id) DESC')
            ->pluck('song_titles.composer_id')
            ->map(fn (mixed $id): ?TrendingArtist => $publicArtists->get((int) $id))
            ->filter()
            ->take(self::RELATED_LIMIT)
            ->values();
    }

    /**
     * Other public pieces that share programs with this one.
     *
     * @return Collection<int, TrendingPiece>
     */
    private function alsoProgrammed(SongTitle $song): Collection
    {
        $programIds = $this->performancesQuery(fn (Builder $query) => $query->where('song_titles.id', $song->id))
            ->select('programs.id');

        $relatedSongIds = SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->whereIn('program_song_title.program_id', $programIds)
            ->where('song_titles.id', '!=', $song->id)
            ->groupBy('song_titles.id')
            ->orderByRaw('COUNT(DISTINCT program_song_title.program_id) DESC')
            ->pluck('song_titles.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $this->trends->isPublicSongTitle($id))
            ->take(self::RELATED_LIMIT)
            ->all();

        if ($relatedSongIds === []) {
            return collect();
        }

        return $this->trends->publicPieces(fn (Builder $query) => $query->whereIn('song_titles.id', $relatedSongIds));
    }

    /**
     * @return list<string>
     */
    private function voicings(SongTitle $song): array
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
     * Only AI- and publisher-sourced tags are public; nothing a director wrote is shown.
     *
     * @return list<string>
     */
    private function tags(SongTitle $song): array
    {
        return SongTitleTag::query()
            ->where('song_title_id', $song->id)
            ->whereIn('authorship_type', [AuthorshipType::Ai->value, AuthorshipType::Publisher->value])
            ->pluck('tag')
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->countBy(fn (string $tag): string => mb_strtolower($tag))
            ->sortDesc()
            ->keys()
            ->take(self::TAG_LIMIT)
            ->map(fn (mixed $tag): string => (string) $tag)
            ->values()
            ->all();
    }

    /**
     * The AI-written summary only: publisher copy is quoted from publisher sites
     * (duplicate content, copyright) and director-written text stays private.
     */
    private function aiSummary(SongTitle $song): ?string
    {
        $description = SongTitleDescription::query()
            ->where('song_title_id', $song->id)
            ->where('authorship_type', AuthorshipType::Ai->value)
            ->latest()
            ->value('description');

        return is_string($description) && trim($description) !== '' ? trim($description) : null;
    }

    /**
     * @param  callable(Builder<SongTitle>): mixed  $constraint
     * @return Builder<SongTitle>
     */
    private function performancesQuery(callable $constraint): Builder
    {
        $query = SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id');

        $constraint($query);

        return $query;
    }
}
