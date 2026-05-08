<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Newsletter>
 */
class NewsletterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'headline' => fake()->sentence(4),
            'intro' => fake()->paragraph(),
            'sections' => [
                [
                    'title' => fake()->sentence(3),
                    'body' => '<p>'.fake()->paragraph().'</p>',
                ],
            ],
            'cta_text' => 'Learn More',
            'cta_url' => fake()->url(),
            'closing' => fake()->paragraph(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'sent_at' => now(),
            'recipient_count' => fake()->numberBetween(10, 50),
        ]);
    }
}
