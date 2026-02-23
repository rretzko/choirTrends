<?php

declare(strict_types=1);

namespace App\Livewire\Schools;

use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use ChecksProgramCompliance;

    public string $filter = 'all';

    public string $sortBy = 'school_name';

    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function render(): View
    {
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        $query = School::query()
            ->with('users.privacy')
            ->withCount(['programs', 'ensembles']);

        if ($this->filter === 'my') {
            $query->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id());
            });
        }

        if (in_array($this->sortBy, ['school_name', 'programs_count', 'ensembles_count'])) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $schools = $query->get();

        // Apply privacy masking
        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        /** @var array<int, string> $displayNames */
        $displayNames = [];
        foreach ($schools as $school) {
            $displayNames[$school->id] = $this->getDisplayName($school, $currentUserId);
        }

        $schoolIds = $schools->pluck('id');
        $songsCounts = $this->getSongsCounts($schoolIds);
        $artistsCounts = $this->getArtistsCounts($schoolIds);

        if (in_array($this->sortBy, ['songs_count', 'artists_count'])) {
            $counts = $this->sortBy === 'songs_count' ? $songsCounts : $artistsCounts;
            $schools = $schools->sortBy(
                fn (School $school) => $counts[$school->id] ?? 0,
                SORT_NUMERIC,
                $this->sortDirection === 'desc',
            )->values();
        }

        $myCount = School::whereHas('users', function ($q) {
            $q->where('users.id', Auth::id());
        })->count();
        $allCount = School::count();

        return view('livewire.schools.index', [
            'schools' => $schools,
            'displayNames' => $displayNames,
            'songsCounts' => $songsCounts,
            'artistsCounts' => $artistsCounts,
            'myCount' => $myCount,
            'allCount' => $allCount,
        ])->layout('components.layouts.app', ['title' => __('Schools')]);
    }

    /**
     * @param  Collection<int, int>  $schoolIds
     * @return Collection<int, int>
     */
    private function getSongsCounts(Collection $schoolIds): Collection
    {
        if ($schoolIds->isEmpty()) {
            return collect();
        }

        return Program::query()
            ->join('program_song_title', 'programs.id', '=', 'program_song_title.program_id')
            ->whereIn('programs.school_id', $schoolIds)
            ->groupBy('programs.school_id')
            ->selectRaw('programs.school_id, COUNT(DISTINCT program_song_title.song_title_id) as count')
            ->pluck('count', 'school_id');
    }

    /**
     * @param  Collection<int, int>  $schoolIds
     * @return Collection<int, int>
     */
    private function getArtistsCounts(Collection $schoolIds): Collection
    {
        if ($schoolIds->isEmpty()) {
            return collect();
        }

        $composerQuery = DB::table('programs')
            ->join('program_song_title', 'programs.id', '=', 'program_song_title.program_id')
            ->join('song_titles', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->whereIn('programs.school_id', $schoolIds)
            ->whereNotNull('song_titles.composer_id')
            ->select('programs.school_id', DB::raw('song_titles.composer_id as artist_id'));

        $arrangerQuery = DB::table('programs')
            ->join('program_song_title', 'programs.id', '=', 'program_song_title.program_id')
            ->join('song_titles', 'program_song_title.song_title_id', '=', 'song_titles.id')
            ->whereIn('programs.school_id', $schoolIds)
            ->whereNotNull('song_titles.arranger_id')
            ->select('programs.school_id', DB::raw('song_titles.arranger_id as artist_id'));

        return DB::query()
            ->fromSub($composerQuery->union($arrangerQuery), 'combined')
            ->groupBy('school_id')
            ->selectRaw('school_id, COUNT(DISTINCT artist_id) as count')
            ->pluck('count', 'school_id');
    }

    private function getDisplayName(School $school, int $currentUserId): string
    {
        // Check if current user owns this school
        $isOwner = $school->users->contains('id', $currentUserId);
        if ($isOwner) {
            return $school->school_name;
        }

        // Check if any owner has school privacy enabled
        /** @var User $user */
        foreach ($school->users as $user) {
            if ($user->privacy?->school) {
                return 'School'.$school->id;
            }
        }

        return $school->school_name;
    }
}
