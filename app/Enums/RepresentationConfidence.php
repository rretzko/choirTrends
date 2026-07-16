<?php

declare(strict_types=1);

namespace App\Enums;

enum RepresentationConfidence: string
{
    case Confirmed = 'Confirmed';
    case Likely = 'Likely';
    case Disputed = 'Disputed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Likely => 'Likely',
            self::Disputed => 'Disputed',
        };
    }
}
