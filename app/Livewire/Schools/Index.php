<?php

declare(strict_types=1);

namespace App\Livewire\Schools;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'all';

    public function render(): View
    {
        $query = School::query()->with('users.privacy');

        if ($this->filter === 'my') {
            $query->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id());
            });
        }

        $schools = $query->orderBy('school_name')->get();

        // Apply privacy masking
        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        /** @var array<int, string> $displayNames */
        $displayNames = [];
        foreach ($schools as $school) {
            $displayNames[$school->id] = $this->getDisplayName($school, $currentUserId);
        }

        $myCount = School::whereHas('users', function ($q) {
            $q->where('users.id', Auth::id());
        })->count();
        $allCount = School::count();

        return view('livewire.schools.index', [
            'schools' => $schools,
            'displayNames' => $displayNames,
            'myCount' => $myCount,
            'allCount' => $allCount,
        ])->layout('components.layouts.app', ['title' => __('Schools')]);
    }

    private function getDisplayName(School $school, int $currentUserId): string
    {
        // Check if current user owns this school
        $isOwner = $school->users->contains('id', $currentUserId);
        if ($isOwner) {
            return $school->school_name;
        }

        // Check if any owner has school privacy enabled
        /** @var User $user */
        foreach ($school->users as $user) {
            if ($user->privacy?->school) {
                return 'School'.$school->id;
            }
        }

        return $school->school_name;
    }
}
