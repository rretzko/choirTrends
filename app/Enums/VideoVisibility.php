<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoVisibility: string
{
    case Private = 'Private';
    case Public = 'Public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Public => 'Public',
        };
    }
}
