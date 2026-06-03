<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class ChangeUserPassword extends Component
{
    public int|string $userId = '';

    public function save(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $this->validate([
            'userId' => ['required', 'exists:users,id'],
        ], [
            'userId.required' => 'Please select a user.',
            'userId.exists' => 'The selected user does not exist.',
        ]);

        $user = User::findOrFail((int) $this->userId);

        $user->update(['password' => Hash::make(strtolower($user->email))]);

        session()->flash('success', __('Password for :name has been set to :email.', ['name' => $user->name, 'email' => strtolower($user->email)]));

        $this->redirect(route('founder.changeUserPassword'), navigate: true);
    }

    public function render(): View
    {
        $users = User::query()
            ->orderBy('alpha_name')
            ->get();

        return view('livewire.founder.change-user-password', [
            'users' => $users,
        ])->layout('components.layouts.app', ['title' => __('Change User Password')]);
    }
}
