<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Models\Ensemble;
use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'my';

    public ?Program $selectedProgram = null;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $songsByEnsemble;

    public function mount(): void
    {
        /** @var Collection<int, array<string, mixed>> $empty */
        $empty = collect();
        $this->songsByEnsemble = $empty;
    }

    public function showProgramDetails(int $programId): void
    {
        $this->selectedProgram = Program::with(['school', 'user.privacy', 'songTitles.composer', 'songTitles.arranger'])->find($programId);

        // Group songs by ensemble
        $this->songsByEnsemble = $this->getSongsByEnsemble();

        $this->modal('program-details')->show();
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

        // Get all ensemble IDs from the pivot table
        $ensembleIds = $this->selectedProgram->songTitles
            ->pluck('pivot.ensemble_id')
            ->filter()
            ->unique()
            ->values();

        // Load ensembles
        $ensembles = Ensemble::whereIn('id', $ensembleIds)->get()->keyBy('id');

        // Group songs by ensemble_id
        $grouped = $this->selectedProgram->songTitles->groupBy('pivot.ensemble_id');

        // Build the result with ensemble objects
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

    public function render(): View
    {
        $query = Program::query()->with(['school', 'user.privacy']);

        if ($this->filter === 'my') {
            $query->where('user_id', Auth::id());
        }

        $programs = $query->orderBy('event_date', 'desc')->get();

        // Apply privacy masking
        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        /** @var array<int, array{school: string, director: string}> $displayData */
        $displayData = [];
        foreach ($programs as $program) {
            $displayData[$program->id] = [
                'school' => $this->getDisplaySchool($program, $currentUserId),
                'director' => $this->getDisplayDirector($program, $currentUserId),
            ];
        }

        return view('livewire.programs.index', [
            'programs' => $programs,
            'displayData' => $displayData,
        ])->layout('components.layouts.app', ['title' => __('Programs')]);
    }

    private function getDisplaySchool(Program $program, int $currentUserId): string
    {
        // If viewing own program, show real value
        if ($program->user_id === $currentUserId) {
            return $program->school->school_name ?? '';
        }

        // If owner has school privacy enabled, mask it
        if ($program->user->privacy?->school) {
            return 'School'.$program->school_id;
        }

        return $program->school->school_name ?? '';
    }

    private function getDisplayDirector(Program $program, int $currentUserId): string
    {
        // If viewing own program, show real value
        if ($program->user_id === $currentUserId) {
            return $program->director_name ?? '';
        }

        // If owner has name privacy enabled, mask it
        if ($program->user->privacy?->name) {
            return 'Director'.$program->user_id;
        }

        return $program->director_name ?? '';
    }
}
