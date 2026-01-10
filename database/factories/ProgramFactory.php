<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
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
            'school_id' => School::factory(),
            'event_name' => fake()->sentence(3),
            'event_date' => fake()->date(),
            'director_name' => fake()->name(),
        ];
    }
}
