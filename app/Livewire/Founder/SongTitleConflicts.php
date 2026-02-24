<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\SongTitle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response;

class SongTitleConflicts extends Component
{
    use WithPagination;

    /**
     * @return LengthAwarePaginator<int, Collection<int, SongTitle>>
     */
    protected function getConflictGroups(): LengthAwarePaginator
    {
        $conflictingTitles = DB::table('song_titles as a')
            ->join('song_titles as b', function ($join) {
                $join->on('a.song_title', '=', 'b.song_title')
                    ->on('a.id', '<', 'b.id')
                    ->where(function ($query) {
                        $query->whereColumn('a.composer_id', '!=', 'b.composer_id')
                            ->orWhereNull('a.composer_id')
                            ->orWhereNull('b.composer_id')
                            ->orWhereColumn('a.arranger_id', '!=', 'b.arranger_id')
                            ->orWhereNull('a.arranger_id')
                            ->orWhereNull('b.arranger_id');
                    });
            })
            ->select('a.song_title')
            ->distinct()
            ->pluck('song_title');

        $songs = SongTitle::query()
            ->whereIn('song_title', $conflictingTitles)
            ->with(['composer', 'arranger'])
            ->withCount('programs')
            ->orderBy('song_title')
            ->orderBy('id')
            ->get();

        $grouped = $songs->groupBy('song_title')
            ->filter(fn (Collection $group) => $this->hasActualConflict($group));

        $page = $this->getPage();
        $perPage = 10;
        $total = $grouped->count();
        $items = $grouped->slice(($page - 1) * $perPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    /**
     * @param  Collection<int, SongTitle>  $group
     */
    protected function hasActualConflict(Collection $group): bool
    {
        if ($group->count() < 2) {
            return false;
        }

        $composerIds = $group->pluck('composer_id')->unique();
        $arrangerIds = $group->pluck('arranger_id')->unique();

        return $composerIds->count() > 1 || $arrangerIds->count() > 1;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        return view('livewire.founder.song-title-conflicts', [
            'conflictGroups' => $this->getConflictGroups(),
        ])->layout('components.layouts.app', ['title' => __('Song Title Conflicts')]);
    }
}
