<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RepresentationConfidence;
use App\Enums\RepresentationSourceType;
use App\Enums\RepresentationStatus;
use App\Models\Artist;
use App\Models\ArtistRepresentation;
use App\Models\RepresentationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtistRepresentation>
 */
class ArtistRepresentationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'representation_category_id' => RepresentationCategory::factory(),
            'source_type' => fake()->randomElement(RepresentationSourceType::cases()),
            'source_name' => fake()->company(),
            'source_url' => fake()->url(),
            'source_excerpt' => fake()->sentence(),
            'confidence' => fake()->randomElement(RepresentationConfidence::cases()),
            'status' => RepresentationStatus::PendingReview,
            'added_by_user_id' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RepresentationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
