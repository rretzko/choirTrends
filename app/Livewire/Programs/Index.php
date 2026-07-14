<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Enums\SchoolType;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use ChecksProgramCompliance;

    public string $filter = 'all';

    /** @var array<int, string> */
    public array $schoolFilter = [];

    public string $typeFilter = '';

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

    public function updatedFilter(): void
    {
        $this->schoolFilter = $this->getUserSchoolIds();
    }

    public function clearSchoolFilter(): void
    {
        $this->schoolFilter = [];
    }

    public function mount(): void
    {
        /** @var Collection<int, array<string, mixed>> $empty */
        $empty = collect();
        $this->songsByEnsemble = $empty;

        $this->schoolFilter = $this->getUserSchoolIds();
    }

    /** @return array<int, string> */
    private function getUserSchoolIds(): array
    {
        return Program::where('user_id', Auth::id())
            ->distinct()
            ->pluck('school_id')
            ->map(fn (int $id): string => (string) $id)
            ->toArray();
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
            return $group['songs']->min('pivot.sort_order') ?? PHP_INT_MAX;
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

        if ($this->schoolFilter !== []) {
            $query->whereIn('school_id', $this->schoolFilter);
        }

        if ($this->typeFilter !== '') {
            $query->whereHas('school', function ($q) {
                $q->where('school_type', $this->typeFilter);
            });
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

            if ($this->sortBy !== 'event_date') {
                $query->orderBy('programs.event_date', 'desc');
            }
        }

        $programs = $query->get();

        if ($this->sortBy === 'director') {
            $sorted = $programs->sortBy([
                fn (Program $a, Program $b) => $this->sortDirection === 'desc'
                    ? $this->compareDirectorNames($b, $a)
                    : $this->compareDirectorNames($a, $b),
                fn (Program $a, Program $b) => $b->event_date <=> $a->event_date,
            ]);
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

        // Load schools for dropdown (respects privacy, independent of schoolFilter)
        $schools = School::query()
            ->whereHas('programs', function ($q) use ($currentUserId) {
                $this->applyProgramVisibility($q, $currentUserId);
            })
            ->orderBy('school_name')
            ->get();

        $schoolFilterLabel = match (true) {
            $this->schoolFilter === [] => __('All Schools/Orgs'),
            count($this->schoolFilter) === 1 => $schools->firstWhere('id', (int) $this->schoolFilter[0])->school_name ?? __('1 School/Org'),
            default => count($this->schoolFilter).' '.__('Schools/Orgs'),
        };

        // Load type counts for dropdown (matches the visibility of the programs table: 'my' vs 'all', independent of schoolFilter/typeFilter)
        $typeCountsQuery = Program::query()->join('schools', 'programs.school_id', '=', 'schools.id');
        if ($this->filter === 'my') {
            $typeCountsQuery->where('programs.user_id', $currentUserId);
        }
        $typeCounts = $typeCountsQuery
            ->groupBy('schools.school_type')
            ->selectRaw('schools.school_type as school_type, COUNT(*) as aggregate')
            ->pluck('aggregate', 'school_type');

        $schoolTypes = collect(SchoolType::cases())
            ->map(fn (SchoolType $type): array => ['type' => $type, 'count' => (int) ($typeCounts[$type->value] ?? 0)])
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->values();

        return view('livewire.programs.index', [
            'programs' => $programs,
            'displayData' => $displayData,
            'schools' => $schools,
            'schoolFilterLabel' => $schoolFilterLabel,
            'schoolTypes' => $schoolTypes,
        ])->layout('components.layouts.app', ['title' => __('Programs')]);
    }

    /**
     * @param  Builder<Program>  $query
     */
    private function applyProgramVisibility(Builder $query, int $currentUserId): void
    {
        $query->where(function ($q) use ($currentUserId) {
            $q->where('programs.user_id', $currentUserId);

            if ($this->filter !== 'my') {
                $q->orWhereHas('user', function ($uq) {
                    $uq->whereDoesntHave('privacy')
                        ->orWhereHas('privacy', function ($pq) {
                            $pq->where(function ($inner) {
                                $inner->where('school', false)->orWhereNull('school');
                            });
                        });
                });
            }
        });
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

    private function compareDirectorNames(Program $a, Program $b): int
    {
        $partsA = explode(' ', $a->director_name ?? '');
        $partsB = explode(' ', $b->director_name ?? '');

        $lastA = mb_strtolower(end($partsA));
        $lastB = mb_strtolower(end($partsB));

        if ($lastA !== $lastB) {
            return $lastA <=> $lastB;
        }

        $firstA = mb_strtolower(count($partsA) > 1 ? $partsA[count($partsA) - 2] : '');
        $firstB = mb_strtolower(count($partsB) > 1 ? $partsB[count($partsB) - 2] : '');

        return $firstA <=> $firstB;
    }
}
