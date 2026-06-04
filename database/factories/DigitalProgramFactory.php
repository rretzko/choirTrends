<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DigitalProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalProgram>
 */
class DigitalProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'program_id' => null,
            'theme' => 'Formal',
            'is_published' => false,
            'print_orientation' => 'Portrait',
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function withTheme(string $theme): static
    {
        return $this->state(['theme' => $theme]);
    }
}
