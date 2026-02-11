<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateUser extends Component
{
    public int|string $userId = '';

    public function impersonate(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $this->validate([
            'userId' => ['required', 'exists:users,id'],
        ], [
            'userId.required' => 'Please select a user to impersonate.',
            'userId.exists' => 'The selected user does not exist.',
        ]);

        $userId = (int) $this->userId;

        if ($userId === auth()->id()) {
            $this->addError('userId', 'You cannot impersonate yourself.');

            return;
        }

        $targetUser = User::findOrFail($userId);

        session()->put('impersonating_from', auth()->id());

        Auth::login($targetUser);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        $users = User::query()
            ->where('id', '!=', auth()->id())
            ->orderBy('alpha_name')
            ->get();

        return view('livewire.founder.impersonate-user', [
            'users' => $users,
        ])->layout('components.layouts.app', ['title' => __('Impersonate User')]);
    }
}
