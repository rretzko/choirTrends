<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SongTitle;
use App\Models\SongTitleAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongTitleAssessment>
 */
class SongTitleAssessmentFactory extends Factory
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
            'grade_level_context' => 'high_school',
            'voicing' => 'SATB',
            'difficulty_by_part' => [
                'soprano' => 'moderate',
                'alto' => 'moderate',
                'tenor' => 'easy',
                'bass' => 'easy',
            ],
            'youtube_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9]{11}'),
            'youtube_confidence' => 'found_via_search',
            'youtube_verified_at' => null,
            'citation_urls' => [],
            'model_version' => 'claude-sonnet-4-6',
            'assessed_at' => now(),
        ];
    }
}
