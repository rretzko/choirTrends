<?php

declare(strict_types=1);

namespace App\Enums;

enum RepertoireQuerySource: string
{
    case Welcome = 'welcome';
    case SongTitles = 'song_titles';

    public function label(): string
    {
        return match ($this) {
            self::Welcome => 'Welcome Page (Guest)',
            self::SongTitles => 'Song Titles Page',
        };
    }
}
