<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StatSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatSnapshot>
 */
class StatSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'verified_users_count' => fake()->numberBetween(1, 100),
            'schools_count' => fake()->numberBetween(1, 100),
            'programs_count' => fake()->numberBetween(1, 100),
            'song_titles_count' => fake()->numberBetween(1, 100),
            'captured_at' => now(),
        ];
    }
}
