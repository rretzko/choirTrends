<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Ensemble;
use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use ChecksProgramCompliance;

    public string $filter = 'all';

    public string $sortBy = 'school';

    public string $sortDirection = 'asc';

    public ?Program $selectedProgram = null;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $songsByEnsemble;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

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
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        $query = Program::query()->with(['school', 'user.privacy']);

        if ($this->filter === 'my') {
            $query->where('user_id', Auth::id());
        }

        $query->join('schools', 'programs.school_id', '=', 'schools.id')
            ->select('programs.*');

        if ($this->sortBy !== 'director') {
            $sortColumn = match ($this->sortBy) {
                'event_name' => 'programs.event_name',
                'event_date' => 'programs.event_date',
                default => 'schools.school_name',
            };
            $query->orderBy($sortColumn, $this->sortDirection);
        }

        $programs = $query->get();

        if ($this->sortBy === 'director') {
            $sorted = $programs->sortBy(function (Program $program) {
                $parts = explode(' ', $program->director_name ?? '');
                $lastName = end($parts);
                $firstName = count($parts) > 1 ? $parts[count($parts) - 2] : '';

                return mb_strtolower($lastName).' '.mb_strtolower($firstName);
            }, SORT_STRING, $this->sortDirection === 'desc');
            $programs = $sorted->values();
        }

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
