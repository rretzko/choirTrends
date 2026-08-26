<?php

declare(strict_types=1);

namespace App\Enums;

enum SongTitleOrigin: string
{
    case Performed = 'performed';
    case AiDiscovered = 'ai_discovered';

    public function label(): string
    {
        return match ($this) {
            self::Performed => 'Performed',
            self::AiDiscovered => 'AI Discovered',
        };
    }
}
