<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class CreateUser extends Component
{
    public string $name = '';

    public string $email = '';

    public string $schoolName = '';

    public function save(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'schoolName' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Str::random(32),
        ]);

        if ($this->schoolName !== '') {
            $school = School::firstOrCreate(['school_name' => $this->schoolName]);
            $user->schools()->attach($school);
        }

        $emailAddress = $user->email;
        $emailAddress = 'rick@mfrholdings.com';
        Password::sendResetLink(['email' => $emailAddress]);

        $this->reset(['name', 'email', 'schoolName']);

        $this->modal('create-user')->close();

        session()->flash('success', __(':name has been created and a password reset email has been sent.', ['name' => $user->name]));

        $this->redirect(route('founder.addProgram'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.founder.create-user');
    }
}
