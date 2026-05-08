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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
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
