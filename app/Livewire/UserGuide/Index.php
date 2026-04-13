<?php

declare(strict_types=1);

namespace App\Livewire\UserGuide;

use App\Models\UserGuide;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public ?string $activeSlug = null;

    public function mount(?string $section = null): void
    {
        if ($section) {
            $this->activeSlug = $section;
        }
    }

    public function setSection(string $slug): void
    {
        $this->activeSlug = $slug;
        $this->dispatch('scroll-to-section');
    }

    public function render(): View
    {
        /** @var Collection<int, UserGuide> $sections */
        $sections = UserGuide::query()->published()->ordered()->get();

        if (! $this->activeSlug && $sections->isNotEmpty()) {
            $this->activeSlug = $sections->first()->slug;
        }

        $activeSection = $sections->firstWhere('slug', $this->activeSlug);

        return view('livewire.user-guide.index', [
            'sections' => $sections,
            'activeSection' => $activeSection,
        ])->layout('components.layouts.app', ['title' => __("User's Guide")]);
    }
}
