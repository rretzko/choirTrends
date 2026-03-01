<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
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
            'from_page' => fake()->optional()->url(),
            'type' => fake()->randomElement(FeedbackType::cases()),
            'body' => fake()->paragraph(),
            'file_path' => null,
            'status' => FeedbackStatus::Open,
            'is_private' => false,
        ];
    }

    public function bug(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeedbackType::Bug,
        ]);
    }

    public function enhancement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeedbackType::Enhancement,
        ]);
    }

    public function kudo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeedbackType::Kudo,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::Closed,
        ]);
    }
}
