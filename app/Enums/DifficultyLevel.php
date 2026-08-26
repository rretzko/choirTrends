<?php

declare(strict_types=1);

namespace App\Enums;

enum DifficultyLevel: string
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Challenging = 'challenging';

    public function numericValue(): int
    {
        return match ($this) {
            self::Easy => 1,
            self::Moderate => 2,
            self::Challenging => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Moderate => 'Moderate',
            self::Challenging => 'Challenging',
        };
    }
}
