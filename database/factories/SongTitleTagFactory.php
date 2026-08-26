<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthorshipType;
use App\Models\SongTitle;
use App\Models\SongTitleTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongTitleTag>
 */
class SongTitleTagFactory extends Factory
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
            'tag' => fake()->randomElement(['sacred', 'secular', 'a cappella', 'contemporary', 'holiday', 'lyrical']),
            'authorship_type' => AuthorshipType::Ai->value,
            'authorship_id' => null,
            'repertoire_query_id' => null,
        ];
    }
}
