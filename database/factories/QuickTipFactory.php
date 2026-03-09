<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuickTipStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuickTip>
 */
class QuickTipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sort_order' => fake()->unique()->numberBetween(1, 10000),
            'header' => fake()->sentence(4),
            'introduction' => null,
            'tip' => '<p>'.fake()->paragraph().'</p>',
            'footer' => null,
            'call_to_action' => null,
            'status' => QuickTipStatus::Draft,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuickTipStatus::Pending,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuickTipStatus::Sent,
        ]);
    }

    public function withIntroduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'introduction' => '<p>'.fake()->paragraph().'</p>',
        ]);
    }

    public function withFooter(): static
    {
        return $this->state(fn (array $attributes) => [
            'footer' => fake()->sentence(),
        ]);
    }

    public function withCallToAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_to_action' => fake()->sentence(),
        ]);
    }
}
