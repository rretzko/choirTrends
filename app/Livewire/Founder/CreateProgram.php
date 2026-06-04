<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\DigitalProgram;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class CreateProgram extends Component
{
    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $recentDigitalPrograms = DigitalProgram::query()
            ->with(['program', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.founder.create-program', [
            'recentDigitalPrograms' => $recentDigitalPrograms,
        ])->layout('components.layouts.app', ['title' => __('Create Program')]);
    }
}
