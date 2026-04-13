<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\UserGuide;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class UserGuideEditor extends Component
{
    public ?int $editingId = null;

    public string $editTitle = '';

    public string $editBody = '';

    public int $editSortOrder = 0;

    public bool $editIsPublished = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);
    }

    public function editSection(int $id): void
    {
        $section = UserGuide::query()->findOrFail($id);

        $this->editingId = $section->id;
        $this->editTitle = $section->title;
        $this->editBody = $section->body;
        $this->editSortOrder = $section->sort_order;
        $this->editIsPublished = $section->is_published;
    }

    public function saveSection(): void
    {
        $validated = $this->validate([
            'editTitle' => ['required', 'string', 'max:255'],
            'editBody' => ['required', 'string'],
            'editSortOrder' => ['required', 'integer', 'min:1'],
            'editIsPublished' => ['boolean'],
        ]);

        $section = UserGuide::query()->findOrFail($this->editingId);

        $section->update([
            'title' => $validated['editTitle'],
            'body' => $validated['editBody'],
            'sort_order' => $validated['editSortOrder'],
            'is_published' => $validated['editIsPublished'],
        ]);

        $this->cancelEdit();
        session()->flash('success', 'Guide section updated successfully!');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editTitle = '';
        $this->editBody = '';
        $this->editSortOrder = 0;
        $this->editIsPublished = true;
    }

    public function moveUp(int $id): void
    {
        $this->swapOrder($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->swapOrder($id, 'down');
    }

    public function render(): View
    {
        /** @var Collection<int, UserGuide> $sections */
        $sections = UserGuide::query()->ordered()->get();

        return view('livewire.founder.user-guide-editor', [
            'sections' => $sections,
        ])->layout('components.layouts.app', ['title' => __('Edit User Guide')]);
    }

    private function swapOrder(int $id, string $direction): void
    {
        $sections = UserGuide::query()->ordered()->get();
        $current = $sections->firstWhere('id', $id);

        if (! $current) {
            return;
        }

        $currentIndex = $sections->search(fn (UserGuide $s): bool => $s->id === $id);

        $swapIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($swapIndex < 0 || $swapIndex >= $sections->count()) {
            return;
        }

        $swapSection = $sections[$swapIndex];
        $tempOrder = $current->sort_order;

        $current->update(['sort_order' => $swapSection->sort_order]);
        $swapSection->update(['sort_order' => $tempOrder]);
    }
}
