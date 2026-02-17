<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserLogin>
 */
class UserLoginFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'device' => fake()->randomElement(['desktop', 'mobile']),
            'viewport' => fake()->randomElement(['1920x1080', '1366x768', '390x844', '1440x900']),
            'counter' => 1,
            'created_at' => now(),
        ];
    }
}
