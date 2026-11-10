<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Artist;
use App\Models\Program;
use App\Models\SongTitle;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Anonymized, aggregate repertoire trends for public (indexable) pages.
 *
 * A piece, composer or arranger is "public" only once it has been performed by
 * at least `trends.minimum_schools` distinct schools. Nothing here ever exposes
 * school, director or student identity.
 */
class PublicTrendsService
{
    private const CACHE_VERSION = 'v3';

    private const GENERATION_KEY = 'public_trends:generation';

    public function minimumSchools(): int
    {
        return (int) config('trends.minimum_schools');
    }

    /**
     * @return Collection<int, TrendingPiece>
     */
    public function topPieces(): Collection
    {
        return $this->remember('pieces', fn (): Collection => $this->publicPieces(limit: $this->listLimit()));
    }

    /**
     * Public pieces ranked by distinct schools, optionally narrowed by a constraint on song_titles.
     *
     * @param  (Closure(Builder<SongTitle>): mixed)|null  $constraint
     * @return Collection<int, TrendingPiece>
     */
    public function publicPieces(?Closure $constraint = null, ?int $limit = null): Collection
    {
        $publicArtistIds = $this->publicArtistIdLookup();

        return SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->leftJoin('artists as composers', 'composers.id', '=', 'song_titles.composer_id')
            ->leftJoin('artists as arrangers', 'arrangers.id', '=', 'song_titles.arranger_id')
            ->when($constraint, fn (Builder $query) => $constraint($query))
            ->select([
                'song_titles.id',
                'song_titles.song_title',
                'song_titles.composer_id',
                'song_titles.arranger_id',
                'composers.artist_name as composer_name',
                'arrangers.artist_name as arranger_name',
            ])
            ->selectRaw('COUNT(DISTINCT programs.school_id) as school_count')
            ->selectRaw('COUNT(*) as performance_count')
            ->groupBy(
                'song_titles.id',
                'song_titles.song_title',
                'song_titles.composer_id',
                'song_titles.arranger_id',
                'composers.artist_name',
                'arrangers.artist_name',
            )
            ->havingRaw('COUNT(DISTINCT programs.school_id) >= ?', [$this->minimumSchools()])
            ->orderByDesc('school_count')
            ->orderByDesc('performance_count')
            ->orderBy('song_titles.song_title')
            ->when($limit, fn (Builder $query) => $query->limit((int) $limit))
            ->get()
            ->toBase()
            ->map(function (SongTitle $row) use ($publicArtistIds): TrendingPiece {
                $composerName = $this->nullableString($row->getAttribute('composer_name'));
                $arrangerName = $this->nullableString($row->getAttribute('arranger_name'));

                return new TrendingPiece(
                    id: $row->id,
                    title: (string) $row->song_title,
                    url: $this->songUrl($row->id, (string) $row->song_title),
                    composer: $composerName,
                    composerUrl: $this->publicArtistUrl($publicArtistIds, $row->composer_id, $composerName),
                    arranger: $arrangerName,
                    arrangerUrl: $this->publicArtistUrl($publicArtistIds, $row->arranger_id, $arrangerName),
                    schoolCount: (int) $row->getAttribute('school_count'),
                    performanceCount: (int) $row->getAttribute('performance_count'),
                );
            });
    }

    /**
     * @return Collection<int, TrendingArtist>
     */
    public function topComposers(): Collection
    {
        return $this->remember('composers', fn (): Collection => $this->topArtists('composer_id', $this->listLimit()));
    }

    /**
     * @return Collection<int, TrendingArtist>
     */
    public function topArrangers(): Collection
    {
        return $this->remember('arrangers', fn (): Collection => $this->topArtists('arranger_id', $this->listLimit()));
    }

    /**
     * Every public artist (composer or arranger role combined), ranked by distinct schools.
     *
     * @return Collection<int, TrendingArtist>
     */
    public function allPublicArtists(): Collection
    {
        return $this->remember('all_artists', fn (): Collection => $this->topArtists(null, null));
    }

    /**
     * Headline totals across all uploaded programs. Counts only, no identities.
     *
     * @return array{program_count: int, school_count: int, piece_count: int, performance_count: int}
     */
    public function headlineStats(): array
    {
        return $this->remember('stats', fn (): array => [
            'program_count' => Program::query()->has('songTitles')->count(),
            'school_count' => Program::query()->has('songTitles')->distinct()->count('school_id'),
            'piece_count' => SongTitle::query()->has('programs')->count(),
            'performance_count' => SongTitle::query()
                ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
                ->count(),
        ]);
    }

    public function isPublicArtist(int $artistId): bool
    {
        return isset($this->publicArtistIdLookup()[$artistId]);
    }

    public function isPublicSongTitle(int $songTitleId): bool
    {
        return isset($this->publicSongTitleIdLookup()[$songTitleId]);
    }

    /**
     * @return array<int, true>
     */
    public function publicSongTitleIdLookup(): array
    {
        return $this->remember('public_song_ids', fn (): array => $this->idLookup(SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->groupBy('song_titles.id')
            ->havingRaw('COUNT(DISTINCT programs.school_id) >= ?', [$this->minimumSchools()])
            ->pluck('song_titles.id')));
    }

    /**
     * @return array<int, true>
     */
    public function publicArtistIdLookup(): array
    {
        return $this->remember('public_artist_ids', fn (): array => $this->idLookup(
            $this->artistAggregateQuery(null)->pluck('artists.id'),
        ));
    }

    /**
     * @param  Collection<array-key, mixed>  $ids
     * @return array<int, true>
     */
    private function idLookup(Collection $ids): array
    {
        return array_fill_keys($ids->map(fn (mixed $id): int => (int) $id)->all(), true);
    }

    public function artistUrl(int $artistId, string $artistName): string
    {
        return route('composers.show', ['id' => $artistId, 'slug' => Artist::slugFor($artistName)]);
    }

    public function songUrl(int $songTitleId, string $title): string
    {
        return route('songs.show', ['id' => $songTitleId, 'slug' => SongTitle::slugFor($title)]);
    }

    /**
     * Public artists ranked by distinct schools, optionally narrowed by a constraint on the
     * joined programs/song_titles (e.g. a school-year date window).
     *
     * @param  'composer_id'|'arranger_id'|null  $roleColumn  null counts both roles together
     * @param  (Closure(Builder<Artist>): mixed)|null  $constraint
     * @return Collection<int, TrendingArtist>
     */
    public function publicArtists(?string $roleColumn, ?Closure $constraint = null, ?int $limit = null): Collection
    {
        return $this->topArtists($roleColumn, $limit, $constraint);
    }

    /**
     * @param  'composer_id'|'arranger_id'|null  $roleColumn  null counts both roles together
     * @param  (Closure(Builder<Artist>): mixed)|null  $constraint
     * @return Collection<int, TrendingArtist>
     */
    private function topArtists(?string $roleColumn, ?int $limit, ?Closure $constraint = null): Collection
    {
        return $this->artistAggregateQuery($roleColumn)
            ->when($constraint, fn (Builder $query) => $constraint($query))
            ->select(['artists.id', 'artists.artist_name', 'artists.artist_first_name', 'artists.artist_last_name'])
            ->selectRaw('COUNT(DISTINCT programs.school_id) as school_count')
            ->selectRaw('COUNT(DISTINCT song_titles.id) as piece_count')
            ->selectRaw('COUNT(*) as performance_count')
            ->orderByDesc('school_count')
            ->orderByDesc('performance_count')
            ->orderBy('artists.artist_name')
            ->when($limit, fn (Builder $query) => $query->limit((int) $limit))
            ->get()
            ->toBase()
            ->map(fn (Artist $row): TrendingArtist => new TrendingArtist(
                id: $row->id,
                name: $row->artist_name,
                sortKey: $row->sortKey(),
                url: $this->artistUrl($row->id, $row->artist_name),
                schoolCount: (int) $row->getAttribute('school_count'),
                pieceCount: (int) $row->getAttribute('piece_count'),
                performanceCount: (int) $row->getAttribute('performance_count'),
            ));
    }

    /**
     * Artists joined to their performances, grouped per artist and filtered to the public threshold.
     *
     * @param  'composer_id'|'arranger_id'|null  $roleColumn
     * @return Builder<Artist>
     */
    private function artistAggregateQuery(?string $roleColumn): Builder
    {
        return Artist::query()
            ->join('song_titles', function ($join) use ($roleColumn): void {
                if ($roleColumn !== null) {
                    $join->on("song_titles.{$roleColumn}", '=', 'artists.id');

                    return;
                }

                $join->on('song_titles.composer_id', '=', 'artists.id')
                    ->orOn('song_titles.arranger_id', '=', 'artists.id');
            })
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->tap(fn (Builder $query) => $this->excludePlaceholderArtists($query))
            ->groupBy('artists.id', 'artists.artist_name', 'artists.artist_first_name', 'artists.artist_last_name')
            ->havingRaw('COUNT(DISTINCT programs.school_id) >= ?', [$this->minimumSchools()]);
    }

    /**
     * @param  Builder<Artist>  $query
     */
    private function excludePlaceholderArtists(Builder $query): void
    {
        /** @var array<int, string> $patterns */
        $patterns = config('trends.excluded_artist_patterns', []);

        foreach ($patterns as $pattern) {
            $query->whereRaw('LOWER(artists.artist_name) NOT LIKE ?', [$pattern]);
        }
    }

    /**
     * @param  array<int, true>  $publicArtistIds
     */
    private function publicArtistUrl(array $publicArtistIds, mixed $artistId, ?string $artistName): ?string
    {
        if ($artistName === null || ! is_numeric($artistId) || ! isset($publicArtistIds[(int) $artistId])) {
            return null;
        }

        return $this->artistUrl((int) $artistId, $artistName);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function listLimit(): int
    {
        return (int) config('trends.list_limit');
    }

    /**
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember(
            'public_trends:'.self::CACHE_VERSION.":g{$this->cacheGeneration()}:{$key}:min{$this->minimumSchools()}",
            now()->addMinutes((int) config('trends.cache_minutes')),
            $callback,
        );
    }

    /**
     * Invalidates every cached public trends value at once (after catalog edits such as
     * artist merges). Works on stores without tag support by bumping a key generation.
     */
    public function flush(): void
    {
        Cache::forever(self::GENERATION_KEY, $this->cacheGeneration() + 1);
    }

    private function cacheGeneration(): int
    {
        return (int) Cache::get(self::GENERATION_KEY, 1);
    }
}
