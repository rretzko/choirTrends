<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RepertoireQuerySource;
use App\Models\RepertoireQuery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepertoireQuery>
 */
class RepertoireQueryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'ip_address' => fake()->ipv4(),
            'source' => RepertoireQuerySource::Welcome,
            'query_text' => fake()->sentence(),
            'response' => null,
            'error' => null,
        ];
    }

    public function forUser(?User $user = null): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => ($user ?? User::factory()->create())->id,
            'ip_address' => null,
            'source' => RepertoireQuerySource::SongTitles,
        ]);
    }
}
