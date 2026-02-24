<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\FeedbackEffort>
 */
class FeedbackEffortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days', '-1 hour');
        $minutesToAdd = fake()->numberBetween(5, 120);

        return [
            'feedback_id' => Feedback::factory(),
            'started_at' => $startedAt,
            'stopped_at' => (clone $startedAt)->modify("+{$minutesToAdd} minutes"),
        ];
    }

    /**
     * Indicate the timer is still running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stopped_at' => null,
        ]);
    }
}
