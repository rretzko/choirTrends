<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Models\UserPrivacy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $privacyName = false;

    public bool $privacySchool = false;

    public bool $privacyEnsembleName = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;

        /** @var UserPrivacy|null $privacy */
        $privacy = $user->privacy;
        if ($privacy) {
            $this->privacyName = $privacy->name;
            $this->privacySchool = $privacy->school;
            $this->privacyEnsembleName = $privacy->ensemble_name;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Update the privacy settings for the currently authenticated user.
     */
    public function updatePrivacySettings(): void
    {
        $user = Auth::user();

        UserPrivacy::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $this->privacyName,
                'school' => $this->privacySchool,
                'ensemble_name' => $this->privacyEnsembleName,
            ]
        );

        $this->dispatch('privacy-updated');
    }
}
