<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Models\DigitalProgram;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showDeleteModal = false;

    public ?int $confirmingDeleteId = null;

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function togglePublish(int $id): void
    {
        abort_if(auth()->user()->isAssistant(), 403);

        $dp = DigitalProgram::query()
            ->where('id', $id)
            ->where('user_id', auth()->user()->digitalProgramsOwnerId())
            ->firstOrFail();

        $dp->update(['is_published' => ! $dp->is_published]);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        abort_if(auth()->user()->isAssistant(), 403);

        if (! $this->confirmingDeleteId) {
            return;
        }

        DigitalProgram::query()
            ->where('id', $this->confirmingDeleteId)
            ->where('user_id', auth()->user()->digitalProgramsOwnerId())
            ->firstOrFail()
            ->delete();

        $this->confirmingDeleteId = null;
        $this->showDeleteModal = false;

        session()->flash('success', 'Digital program deleted.');
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        $digitalPrograms = DigitalProgram::query()
            ->where('user_id', auth()->user()->digitalProgramsOwnerId())
            ->with(['program' => fn ($q) => $q->withCount('songTitles')->with('school')])
            ->withCount('rosters')
            ->latest()
            ->get();

        return view('livewire.digital-programs.index', [
            'digitalPrograms' => $digitalPrograms,
        ])->layout('components.layouts.app', ['title' => __('Digital Programs')]);
    }
}
