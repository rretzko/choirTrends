<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserGuide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserGuide>
 */
class UserGuideFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }
}
