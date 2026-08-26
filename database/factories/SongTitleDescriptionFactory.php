<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthorshipType;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongTitleDescription>
 */
class SongTitleDescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'song_title_id' => SongTitle::factory(),
            'description' => fake()->sentence(15),
            'authorship_type' => AuthorshipType::Ai->value,
            'authorship_id' => null,
            'repertoire_query_id' => null,
            'model_version' => 'claude-sonnet-4-6',
        ];
    }
}
