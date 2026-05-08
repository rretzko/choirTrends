<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SongTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongTitle>
 */
class SongTitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $songTitles = [
            'Ave Maria',
            'Lux Aurumque',
            'O Magnum Mysterium',
            'The Seal Lullaby',
            'Sure on This Shining Night',
            'Alleluia',
            'In Paradisum',
            'Daemon Irrepit Callidus',
        ];

        return [
            'song_title' => fake()->unique()->randomElement($songTitles),
        ];
    }
}
