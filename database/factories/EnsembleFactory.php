<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ensemble>
 */
class EnsembleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ensembleTypes = [
            'Concert Choir',
            'Women\'s Ensemble',
            'Men\'s Chorus',
            'Chamber Singers',
            'Varsity Treble Choir',
            'Jazz Choir',
            'A Cappella Group',
            'Show Choir',
        ];

        return [
            'school_id' => School::factory(),
            'ensemble_name' => fake()->randomElement($ensembleTypes),
        ];
    }
}
