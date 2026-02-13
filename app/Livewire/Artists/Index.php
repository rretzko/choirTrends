<?php

declare(strict_types=1);

namespace App\Livewire\Artists;

use App\Models\Artist;
use App\Models\SongTitle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'all';

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
            ->where('composer_id', $artistId)
            ->orWhere('arranger_id', $artistId)
            ->with(['composer', 'arranger'])
            ->orderBy('song_title')
            ->get();

        $this->modal('artist-repertoire')->show();
    }

    public function sort(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
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

        $artists = $query->get();

        return view('livewire.artists.index', [
            'artists' => $artists,
            'myCount' => $myCount,
            'allCount' => $allCount,
        ])->layout('components.layouts.app', ['title' => __('Artists')]);
    }
}
