<?php

declare(strict_types=1);

namespace App\Livewire\Artists;

use App\Enums\SongTitleOrigin;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Artist;
use App\Models\SongTitle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ChecksProgramCompliance;
    use WithPagination;

    public string $filter = 'all';

    public string $search = '';

    public string $sortColumn = 'artist_last_name';

    public string $sortDirection = 'asc';

    public ?Artist $selectedArtist = null;

    /** @var Collection<int, SongTitle> */
    public Collection $repertoire;

    public function mount(): void
    {
        $this->repertoire = new Collection;
    }

    public function showRepertoire(int $artistId): void
    {
        $this->selectedArtist = Artist::find($artistId);

        if (! $this->selectedArtist) {
            return;
        }

        $this->repertoire = SongTitle::query()
            ->where('origin', SongTitleOrigin::Performed)
            ->where(function ($query) use ($artistId) {
                $query->where('composer_id', $artistId)
                    ->orWhere('arranger_id', $artistId);
            })
            ->with(['composer', 'arranger'])
            ->orderBy('song_title')
            ->get();

        $this->modal('artist-repertoire')->show();
    }

    public function sort(string $column): void
    {
        $this->resetPage();

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Get artist IDs associated with the current user's programs.
     *
     * @return array<int>
     */
    private function getMyArtistIds(): array
    {
        $userId = Auth::id();

        return SongTitle::query()
            ->whereHas('programs', fn ($query) => $query->where('user_id', $userId))
            ->selectRaw('DISTINCT composer_id as artist_id')
            ->union(
                SongTitle::query()
                    ->whereHas('programs', fn ($query) => $query->where('user_id', $userId))
                    ->whereNotNull('arranger_id')
                    ->selectRaw('DISTINCT arranger_id as artist_id')
            )
            ->pluck('artist_id')
            ->toArray();
    }

    public function render(): View
    {
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        $myArtistIds = $this->getMyArtistIds();
        $myCount = count($myArtistIds);
        $allCount = Artist::count();

        $repertoireSql = '(
            SELECT COUNT(*)
            FROM program_song_title
            INNER JOIN song_titles ON program_song_title.song_title_id = song_titles.id
            WHERE song_titles.composer_id = artists.id
               OR song_titles.arranger_id = artists.id
        ) AS `repertoire_count`';

        $query = Artist::query()
            ->select(['artists.*'])
            ->selectRaw($repertoireSql)
            ->orderBy($this->sortColumn, $this->sortDirection);

        if ($this->filter === 'my') {
            $query->whereIn('id', $myArtistIds);
        }

        if ($this->search !== '') {
            $searchTerm = '%'.$this->search.'%';
            $query->where('artist_name', 'like', $searchTerm);
        }

        $artists = $query->paginate(20);

        return view('livewire.artists.index', [
            'artists' => $artists,
            'myCount' => $myCount,
            'allCount' => $allCount,
        ])->layout('components.layouts.app', ['title' => __('Artists')]);
    }
}
