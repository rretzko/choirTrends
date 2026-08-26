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

    /**
     * Reverse of numericValue(), rounding to the nearest level and clamping
     * to the valid 1-3 range (an averaged score can fall outside it slightly).
     */
    public static function fromNumericValue(int $value): self
    {
        return match (max(1, min(3, $value))) {
            1 => self::Easy,
            2 => self::Moderate,
            3 => self::Challenging,
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Easy => 'lime',
            self::Moderate => 'amber',
            self::Challenging => 'red',
        };
    }
}
