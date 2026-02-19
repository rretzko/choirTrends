<?php

declare(strict_types=1);

namespace App\Livewire\SongTitles;

use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\SongTitle;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use ChecksProgramCompliance;

    public string $filter = 'all';

    public string $search = '';

    public string $sortBy = 'song_title';

    public string $sortDirection = 'asc';

    public int $myCount = 0;

    public int $allCount = 0;

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

        // Calculate counts for filters
        $this->allCount = SongTitle::query()->count();
        $this->myCount = SongTitle::query()
            ->whereHas('programs', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->count();

        $query = SongTitle::query()
            ->with(['composer', 'arranger'])
            ->select('song_titles.*')
            ->leftJoin('artists as composers', 'song_titles.composer_id', '=', 'composers.id')
            ->leftJoin('artists as arrangers', 'song_titles.arranger_id', '=', 'arrangers.id');

        // Add performed count based on filter
        if ($this->filter === 'my') {
            $query->withCount(['programs as performed_count' => function ($q) {
                $q->where('user_id', Auth::id());
            }]);
            $query->whereHas('programs', function ($q) {
                $q->where('user_id', Auth::id());
            });
        } else {
            $query->withCount('programs as performed_count');
        }

        if ($this->search !== '') {
            $searchTerm = '%'.$this->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('song_titles.song_title', 'like', $searchTerm)
                    ->orWhere('composers.artist_name', 'like', $searchTerm)
                    ->orWhere('arrangers.artist_name', 'like', $searchTerm);
            });
        }

        if (in_array($this->sortBy, ['composer', 'arranger'])) {
            $prefix = $this->sortBy === 'composer' ? 'composers' : 'arrangers';
            $query->orderBy("{$prefix}.artist_last_name", $this->sortDirection)
                ->orderBy("{$prefix}.artist_first_name", $this->sortDirection);
        } else {
            $sortColumn = match ($this->sortBy) {
                'performed' => 'performed_count',
                default => 'song_titles.song_title',
            };
            $query->orderBy($sortColumn, $this->sortDirection);
        }

        $songTitles = $query->get();

        return view('livewire.song-titles.index', [
            'songTitles' => $songTitles,
        ])->layout('components.layouts.app', ['title' => __('Song Titles')]);
    }
}
