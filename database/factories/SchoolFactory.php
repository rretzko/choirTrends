<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' High School';

        return [
            'school_name' => $name,
            'abbreviation' => School::guessAbbreviation($name),
            'postal_code' => fake()->postcode(),
            'geo_state' => fake()->stateAbbr(),
            'country' => 'US',
        ];
    }
}
