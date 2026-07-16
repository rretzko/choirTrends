<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RepresentationGroup;
use App\Models\RepresentationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RepresentationCategory>
 */
class RepresentationCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'group' => fake()->randomElement(RepresentationGroup::cases()),
            'label' => $label,
            'slug' => Str::slug($label),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
