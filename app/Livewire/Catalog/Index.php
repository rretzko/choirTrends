<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Ensemble;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public User $user;

    public string $schoolFilter = '';

    public ?Program $selectedProgram = null;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $songsByEnsemble;

    public ?int $videoSongTitleId = null;

    public ?int $videoProgramId = null;

    public string $videoSongName = '';

    public bool $showConcertVideo = false;

    public bool $isAudio = false;

    public function mount(string $token): void
    {
        $user = User::where('catalog_token', $token)
            ->whereNotNull('catalog_enabled_at')
            ->first();

        if (! $user) {
            abort(404);
        }

        $this->user = $user;

        /** @var Collection<int, array<string, mixed>> $empty */
        $empty = collect();
        $this->songsByEnsemble = $empty;
    }

    public function showProgramDetails(int $programId): void
    {
        $this->selectedProgram = Program::with(['school', 'songTitles.composer', 'songTitles.arranger'])
            ->where('user_id', $this->user->id)
            ->find($programId);

        if (! $this->selectedProgram) {
            return;
        }

        $this->songsByEnsemble = $this->getSongsByEnsemble();

        $this->modal('catalog-program-details')->show();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getSongsByEnsemble(): Collection
    {
        if (! $this->selectedProgram) {
            /** @var Collection<int, array<string, mixed>> $empty */
            $empty = collect();

            return $empty;
        }

        $ensembleIds = $this->selectedProgram->songTitles
            ->pluck('pivot.ensemble_id')
            ->filter()
            ->unique()
            ->values();

        $ensembles = Ensemble::whereIn('id', $ensembleIds)->get()->keyBy('id');

        $grouped = $this->selectedProgram->songTitles->groupBy('pivot.ensemble_id');

        /** @var Collection<int, array<string, mixed>> $result */
        $result = $grouped->map(function ($songs, $ensembleId) use ($ensembles) {
            /** @var Ensemble|null $ensemble */
            $ensemble = $ensembleId ? $ensembles->get($ensembleId) : null;

            return [
                'ensemble' => $ensemble,
                'songs' => $songs,
            ];
        })->sortBy(function ($group) {
            /** @var Ensemble|null $ensemble */
            $ensemble = $group['ensemble'];

            return $ensemble ? $ensemble->ensemble_name : 'zzz';
        })->values();

        return $result;
    }

    public function playSongVideo(int $songTitleId, string $songName, bool $isAudio = false): void
    {
        if (! $this->selectedProgram) {
            return;
        }

        $this->videoSongTitleId = $songTitleId;
        $this->videoProgramId = $this->selectedProgram->id;
        $this->videoSongName = $songName;
        $this->showConcertVideo = false;
        $this->isAudio = $isAudio;

        $this->modal('catalog-video-player')->show();
    }

    public function playConcertVideo(): void
    {
        if (! $this->selectedProgram || ! $this->selectedProgram->video_path) {
            return;
        }

        $this->videoProgramId = $this->selectedProgram->id;
        $this->videoSongTitleId = null;
        $this->videoSongName = '';
        $this->showConcertVideo = true;
        $this->isAudio = false;

        $this->modal('catalog-video-player')->show();
    }

    public function render(): View
    {
        $query = Program::query()
            ->with(['school'])
            ->where('user_id', $this->user->id);

        if ($this->schoolFilter !== '') {
            $query->where('school_id', $this->schoolFilter);
        }

        $query->join('schools', 'programs.school_id', '=', 'schools.id')
            ->select('programs.*')
            ->orderBy('schools.school_name')
            ->orderBy('programs.event_date', 'desc');

        $programs = $query->get();

        $schools = Program::where('user_id', $this->user->id)
            ->join('schools', 'programs.school_id', '=', 'schools.id')
            ->select('schools.id', 'schools.school_name')
            ->distinct()
            ->orderBy('schools.school_name')
            ->get();

        return view('livewire.catalog.index', [
            'programs' => $programs,
            'schools' => $schools,
        ])->layout('components.layouts.catalog', ['title' => __('Program Catalog')]);
    }
}
