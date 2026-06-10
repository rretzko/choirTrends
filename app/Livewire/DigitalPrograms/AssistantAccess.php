<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class AssistantAccess extends Component
{
    public ?string $generatedPassword = null;

    public function createAssistant(): void
    {
        /** @var User $director */
        $director = Auth::user();

        abort_if($director->isAssistant(), 403);

        if ($this->findAssistant($director)) {
            return;
        }

        $password = Str::password(12);

        $assistant = $director->assistant()->make([
            'name' => __('Assistant'),
            'alpha_name' => __('Assistant'),
            'email' => $this->generateAssistantEmail($director),
            'password' => $password,
            'role' => UserRole::Assistant,
        ]);

        $assistant->email_verified_at = now();
        $assistant->save();

        $this->generatedPassword = $password;
    }

    public function resetPassword(): void
    {
        /** @var User $director */
        $director = Auth::user();

        abort_if($director->isAssistant(), 403);

        $assistant = $this->findAssistant($director);

        if (! $assistant) {
            return;
        }

        $password = Str::password(12);

        $assistant->update(['password' => $password]);

        $this->generatedPassword = $password;
    }

    public function removeAssistant(): void
    {
        /** @var User $director */
        $director = Auth::user();

        abort_if($director->isAssistant(), 403);

        $this->findAssistant($director)?->delete();
    }

    private function findAssistant(User $director): ?User
    {
        return User::query()->where('parent_user_id', $director->id)->first();
    }

    private function generateAssistantEmail(User $director): string
    {
        $email = "assistant{$director->id}@assistants.invalid";

        if (! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        do {
            $email = "assistant{$director->id}-".Str::lower(Str::random(4)).'@assistants.invalid';
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }

    public function render(): View
    {
        /** @var User $director */
        $director = Auth::user();

        return view('livewire.digital-programs.assistant-access', [
            'assistant' => $this->findAssistant($director),
        ]);
    }
}
