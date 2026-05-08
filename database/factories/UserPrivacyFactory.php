<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPrivacy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPrivacy>
 */
class UserPrivacyFactory extends Factory
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
            'name' => false,
            'school' => false,
            'ensemble_name' => false,
        ];
    }
}
