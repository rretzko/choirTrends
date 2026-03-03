<?php

declare(strict_types=1);

namespace App\Livewire\SongTitles;

use App\Enums\VideoVisibility;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\SongTitle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public ?int $videoSongTitleId = null;

    public ?int $videoProgramId = null;

    public string $videoSongName = '';

    public bool $isAudio = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function playVideo(int $songTitleId, int $programId, string $songName, bool $isAudio = false): void
    {
        $this->videoSongTitleId = $songTitleId;
        $this->videoProgramId = $programId;
        $this->videoSongName = $songName;
        $this->isAudio = $isAudio;

        $this->modal('song-video-player')->show();
    }

    /**
     * Build a map of song_title_id => [{program_id, program_name, is_audio}] for songs with viewable videos.
     *
     * @return Collection<int|string, array{program_id: int, program_name: string, is_audio: bool}>
     */
    private function getViewableVideoMap(): Collection
    {
        $userId = Auth::id();

        $rows = DB::table('program_song_title')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->whereNotNull('program_song_title.video_path')
            ->where(function ($q) use ($userId) {
                $q->where('programs.user_id', $userId)
                    ->orWhere('program_song_title.video_visibility', VideoVisibility::Public->value);
            })
            ->select('program_song_title.song_title_id', 'programs.id as program_id', 'programs.event_name', 'program_song_title.video_path')
            ->get();

        return $rows->groupBy('song_title_id')->map(function ($items) {
            /** @var object{program_id: int, event_name: string, video_path: string} $row */
            $row = $items->first();

            $ext = strtolower(pathinfo((string) $row->video_path, PATHINFO_EXTENSION));

            return [
                'program_id' => (int) $row->program_id,
                'program_name' => (string) $row->event_name,
                'is_audio' => in_array($ext, ['mp3', 'wav', 'm4a', 'ogg', 'flac', 'aac', 'wma']),
            ];
        });
    }

    public function render(): View
    {
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        // Calculate counts for filters (exclude orphaned song titles with no programs)
        $this->allCount = SongTitle::query()->whereHas('programs')->count();
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
            $query->whereHas('programs');
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

        $viewableVideoMap = $this->getViewableVideoMap();

        return view('livewire.song-titles.index', [
            'songTitles' => $songTitles,
            'viewableVideoMap' => $viewableVideoMap,
        ])->layout('components.layouts.app', ['title' => __('Song Titles')]);
    }
}
