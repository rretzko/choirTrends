<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VideoVisibility;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
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

    /**
     * Indicate that the program has a video uploaded.
     */
    public function withVideo(VideoVisibility $visibility = VideoVisibility::Private): static
    {
        return $this->state(fn (array $attributes) => [
            'video_path' => 'mp4s/programs/'.fake()->sha256().'.mp4',
            'video_visibility' => $visibility,
            'video_uploaded_at' => now(),
        ]);
    }
}
