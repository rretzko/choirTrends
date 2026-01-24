<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Models\Program;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'my';

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
