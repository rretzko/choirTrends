<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $parts = explode(' ', $name);
        $lastName = array_pop($parts);
        $alphaName = count($parts) > 0 ? $lastName.', '.implode(' ', $parts) : $name;

        return [
            'name' => $name,
            'alpha_name' => $alphaName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Indicate that the user is the founder.
     */
    public function founder(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => config('app.founder') ?: 'founder@example.com',
        ]);
    }

    /**
     * Indicate that the user has been welcomed.
     */
    public function welcomed(): static
    {
        return $this->state(fn (array $attributes) => [
            'welcomed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has dismissed onboarding.
     */
    public function onboardingDismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_dismissed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has been sent the orientation email.
     */
    public function orientationEmailSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'orientation_email_sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has catalog sharing enabled.
     */
    public function withCatalog(): static
    {
        return $this->state(fn (array $attributes) => [
            'catalog_token' => Str::uuid()->toString(),
            'catalog_enabled_at' => now(),
        ]);
    }
}
