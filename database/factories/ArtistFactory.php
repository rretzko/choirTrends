<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Artist>
 */
class ArtistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'artist_name' => "{$firstName} {$lastName}",
            'artist_first_name' => $firstName,
            'artist_last_name' => $lastName,
        ];
    }

    /**
     * Indicate the artist is a single-word name (like "Traditional")
     */
    public function singleWord(): static
    {
        return $this->state(fn (array $attributes) => [
            'artist_name' => 'Traditional',
            'artist_first_name' => null,
            'artist_last_name' => 'Traditional',
        ]);
    }
}
