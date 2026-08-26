<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthorshipType: string
{
    case Ai = 'ai';
    case Publisher = 'publisher';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'AI',
            self::Publisher => 'Publisher',
            self::User => 'User',
        };
    }
}
