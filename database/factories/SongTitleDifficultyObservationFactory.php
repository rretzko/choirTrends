<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\VoicePart;
use App\Models\SongTitle;
use App\Models\SongTitleDifficultyObservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongTitleDifficultyObservation>
 */
class SongTitleDifficultyObservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $difficulty = fake()->randomElement(DifficultyLevel::cases());

        return [
            'song_title_id' => SongTitle::factory(),
            'voice_part' => fake()->randomElement(VoicePart::cases())->value,
            'difficulty_label' => $difficulty->value,
            'difficulty_value' => $difficulty->numericValue(),
            'authorship_type' => AuthorshipType::Ai->value,
            'authorship_id' => null,
            'repertoire_query_id' => null,
            'citation_url' => null,
            'model_version' => 'claude-sonnet-4-6',
        ];
    }
}
