<?php

declare(strict_types=1);

namespace App\Livewire\Ensembles;

use App\Enums\EnsembleType;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Ensemble;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use ChecksProgramCompliance;

    public string $filter = 'all';

    public function updateType(int $ensembleId, string $type): void
    {
        $ensemble = Ensemble::findOrFail($ensembleId);
        $ensemble->load('school.users');
        abort_unless($ensemble->school?->users->contains('id', Auth::id()) ?? false, 403);

        $ensembleType = EnsembleType::tryFrom($type);
        abort_unless($ensembleType !== null, 422);

        $ensemble->update(['type' => $ensembleType]);
    }

    public function updateACappella(int $ensembleId, bool $value): void
    {
        $ensemble = Ensemble::findOrFail($ensembleId);
        $ensemble->load('school.users');
        abort_unless($ensemble->school?->users->contains('id', Auth::id()) ?? false, 403);

        $ensemble->update(['a_cappella' => $value]);
    }

    public function render(): View
    {
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        $query = Ensemble::query()->with(['school.users.privacy']);

        if ($this->filter === 'my') {
            $query->whereHas('school.users', function ($q) {
                $q->where('users.id', Auth::id());
            });
        }

        $ensembles = $query
            ->join('schools', 'ensembles.school_id', '=', 'schools.id')
            ->select('ensembles.*')
            ->orderBy('schools.school_name')
            ->orderBy('ensembles.ensemble_name')
            ->get();

        // Apply privacy masking and ownership check
        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        /** @var array<int, string> $displayNames */
        $displayNames = [];

        /** @var array<int, string> $schoolDisplayNames */
        $schoolDisplayNames = [];

        /** @var array<int, bool> $ownedEnsembleIds */
        $ownedEnsembleIds = [];

        foreach ($ensembles as $ensemble) {
            $displayNames[$ensemble->id] = $this->getDisplayName($ensemble, $currentUserId);
            $schoolDisplayNames[$ensemble->id] = $this->getDisplaySchoolName($ensemble, $currentUserId);
            $ownedEnsembleIds[$ensemble->id] = $ensemble->school?->users->contains('id', $currentUserId) ?? false;
        }

        $myCount = Ensemble::whereHas('school.users', function ($q) {
            $q->where('users.id', Auth::id());
        })->count();
        $allCount = Ensemble::count();

        return view('livewire.ensembles.index', [
            'ensembles' => $ensembles,
            'displayNames' => $displayNames,
            'schoolDisplayNames' => $schoolDisplayNames,
            'ownedEnsembleIds' => $ownedEnsembleIds,
            'myCount' => $myCount,
            'allCount' => $allCount,
        ])->layout('components.layouts.app', ['title' => __('Ensembles')]);
    }

    private function getDisplayName(Ensemble $ensemble, int $currentUserId): string
    {
        /** @var School|null $school */
        $school = $ensemble->school;
        if (! $school) {
            return $ensemble->ensemble_name;
        }

        // Check if current user owns this school
        $isOwner = $school->users->contains('id', $currentUserId);
        if ($isOwner) {
            return $ensemble->ensemble_name;
        }

        // Check if any owner has ensemble_name privacy enabled
        /** @var User $user */
        foreach ($school->users as $user) {
            if ($user->privacy?->ensemble_name) {
                return 'Ensemble'.$ensemble->id;
            }
        }

        return $ensemble->ensemble_name;
    }

    private function getDisplaySchoolName(Ensemble $ensemble, int $currentUserId): string
    {
        /** @var School|null $school */
        $school = $ensemble->school;
        if (! $school) {
            return '';
        }

        // Check if current user owns this school
        $isOwner = $school->users->contains('id', $currentUserId);
        if ($isOwner) {
            return $school->school_name;
        }

        // Check if any owner has school privacy enabled
        /** @var User $user */
        foreach ($school->users as $user) {
            if ($user->privacy?->school) {
                return 'School'.$user->id;
            }
        }

        return $school->school_name;
    }
}
