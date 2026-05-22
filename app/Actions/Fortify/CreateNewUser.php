<?php

namespace App\Actions\Fortify;

use App\Enums\ReferralSource;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $throttleKey = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [__('Too many registration attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $trimmed = trim((string) $value);
                    if (! preg_match('/\S+\s+\S+/', $trimmed)) {
                        $fail(__('Please enter your full name (first and last name required).'));

                        return;
                    }
                    if (! preg_match('/^[\p{L}\s\'\-\.]+$/u', $trimmed)) {
                        $fail(__('The name may only contain letters, spaces, hyphens, and apostrophes.'));
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = strtolower(trim((string) $value));

                    if (! str_ends_with($email, '@gmail.com') && ! str_ends_with($email, '@googlemail.com')) {
                        return;
                    }

                    $local = explode('@', $email)[0];
                    $segments = explode('.', $local);

                    if (count($segments) >= 4) {
                        $shortCount = count(array_filter($segments, fn (string $s): bool => strlen($s) <= 2));
                        if ($shortCount / count($segments) >= 0.6) {
                            $fail(__('Registration failed. Please try again.'));

                            return;
                        }
                    }

                    $normalized = str_replace('.', '', $local);
                    $exists = User::query()
                        ->whereRaw("REPLACE(LOWER(SUBSTR(email, 1, INSTR(email, '@') - 1)), '.', '') = ?", [$normalized])
                        ->where(function ($q): void {
                            $q->where('email', 'like', '%@gmail.com')
                                ->orWhere('email', 'like', '%@googlemail.com');
                        })
                        ->whereNotNull('email_verified_at')
                        ->exists();

                    if ($exists) {
                        $fail(__('An account with this email address already exists.'));
                    }
                },
            ],
            'password' => $this->passwordRules(),
            'referral_source' => ['nullable', Rule::enum(ReferralSource::class)],
            'referral_detail' => ['nullable', 'string', 'max:255'],
            'viewport' => ['required', 'regex:/^\d+x\d+$/'],
        ], [
            'viewport.required' => __('Registration failed. Please try again.'),
            'viewport.regex' => __('Registration failed. Please try again.'),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'referral_source' => $input['referral_source'] ?? null,
            'referral_detail' => $input['referral_detail'] ?? null,
        ]);
    }
}
