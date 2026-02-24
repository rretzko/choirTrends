<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WelcomeModal extends Component
{
    public bool $showWelcomeModal = false;

    public function mount(): void
    {
        $this->showWelcomeModal = Auth::user()->welcomed_at === null;
    }

    public function dismiss(): void
    {
        Auth::user()->update(['welcomed_at' => now()]);

        $this->showWelcomeModal = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.onboarding.welcome-modal');
    }
}
