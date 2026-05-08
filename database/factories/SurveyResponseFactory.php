<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SurveyReason;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reasons' => [SurveyReason::NoTime->value],
            'comments' => fake()->optional()->sentence(),
        ];
    }
}
