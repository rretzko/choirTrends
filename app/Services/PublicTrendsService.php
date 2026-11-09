<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Artist;
use App\Models\Program;
use App\Models\SongTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Anonymized, aggregate repertoire trends for public (indexable) pages.
 *
 * Every list only includes items performed by at least `trends.minimum_schools`
 * distinct schools, and never exposes school, director or student identity.
 */
class PublicTrendsService
{
    public function minimumSchools(): int
    {
        return (int) config('trends.minimum_schools');
    }

    /**
     * @return Collection<int, TrendingPiece>
     */
    public function topPieces(): Collection
    {
        return $this->remember('pieces', fn (): Collection => $this->queryTopPieces());
    }

    /**
     * @return Collection<int, TrendingPiece>
     */
    private function queryTopPieces(): Collection
    {
        return SongTitle::query()
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->leftJoin('artists as composers', 'composers.id', '=', 'song_titles.composer_id')
            ->leftJoin('artists as arrangers', 'arrangers.id', '=', 'song_titles.arranger_id')
            ->select([
                'song_titles.id',
                'song_titles.song_title',
                'composers.artist_name as composer_name',
                'arrangers.artist_name as arranger_name',
            ])
            ->selectRaw('COUNT(DISTINCT programs.school_id) as school_count')
            ->selectRaw('COUNT(*) as performance_count')
            ->groupBy('song_titles.id', 'song_titles.song_title', 'composers.artist_name', 'arrangers.artist_name')
            ->havingRaw('COUNT(DISTINCT programs.school_id) >= ?', [$this->minimumSchools()])
            ->orderByDesc('school_count')
            ->orderByDesc('performance_count')
            ->orderBy('song_titles.song_title')
            ->limit($this->listLimit())
            ->get()
            ->map(fn (SongTitle $row): TrendingPiece => new TrendingPiece(
                title: (string) $row->song_title,
                composer: $this->nullableString($row->getAttribute('composer_name')),
                arranger: $this->nullableString($row->getAttribute('arranger_name')),
                schoolCount: (int) $row->getAttribute('school_count'),
                performanceCount: (int) $row->getAttribute('performance_count'),
            ));
    }

    /**
     * @return Collection<int, TrendingArtist>
     */
    public function topComposers(): Collection
    {
        return $this->remember('composers', fn (): Collection => $this->topArtists('composer_id'));
    }

    /**
     * @return Collection<int, TrendingArtist>
     */
    public function topArrangers(): Collection
    {
        return $this->remember('arrangers', fn (): Collection => $this->topArtists('arranger_id'));
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

    /**
     * @param  'composer_id'|'arranger_id'  $roleColumn
     * @return Collection<int, TrendingArtist>
     */
    private function topArtists(string $roleColumn): Collection
    {
        return Artist::query()
            ->join('song_titles', "song_titles.{$roleColumn}", '=', 'artists.id')
            ->join('program_song_title', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->tap(fn (Builder $query) => $this->excludePlaceholderArtists($query))
            ->select(['artists.id', 'artists.artist_name'])
            ->selectRaw('COUNT(DISTINCT programs.school_id) as school_count')
            ->selectRaw('COUNT(DISTINCT song_titles.id) as piece_count')
            ->selectRaw('COUNT(*) as performance_count')
            ->groupBy('artists.id', 'artists.artist_name')
            ->havingRaw('COUNT(DISTINCT programs.school_id) >= ?', [$this->minimumSchools()])
            ->orderByDesc('school_count')
            ->orderByDesc('performance_count')
            ->orderBy('artists.artist_name')
            ->limit($this->listLimit())
            ->get()
            ->map(fn (Artist $row): TrendingArtist => new TrendingArtist(
                name: $row->artist_name,
                schoolCount: (int) $row->getAttribute('school_count'),
                pieceCount: (int) $row->getAttribute('piece_count'),
                performanceCount: (int) $row->getAttribute('performance_count'),
            ));
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
    private function remember(string $key, callable $callback): mixed
    {
        return Cache::remember(
            "public_trends:{$key}:min{$this->minimumSchools()}",
            now()->addMinutes((int) config('trends.cache_minutes')),
            $callback,
        );
    }
}
