<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Mail\OrientationEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SetupChecklist extends Component
{
    public bool $hasSchool = false;

    public bool $hasProgram = false;

    public bool $hasPrivacy = false;

    public bool $isDismissed = false;

    public bool $isComplete = false;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->onboarding_dismissed_at !== null) {
            $this->isDismissed = true;

            return;
        }

        if ($user->orientation_email_sent_at === null && ! session()->has('impersonating_from')) {
            Mail::to($user->email)->send(new OrientationEmail($user));
            $user->update(['orientation_email_sent_at' => now()]);
        }

        $this->hasSchool = $user->schools()->exists();
        $this->hasProgram = $user->programs()->exists();
        $this->hasPrivacy = $user->privacy()->exists();
        $this->isComplete = $this->hasSchool && $this->hasProgram && $this->hasPrivacy;
    }

    public function dismiss(): void
    {
        Auth::user()->update(['onboarding_dismissed_at' => now()]);

        $this->isDismissed = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.onboarding.setup-checklist');
    }
}
