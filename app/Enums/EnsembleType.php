<?php

declare(strict_types=1);

namespace App\Enums;

enum EnsembleType: string
{
    case Satb = 'Satb';
    case SopranoAlto = 'SopranoAlto';
    case TenorBass = 'TenorBass';
    case Unknown = 'Unknown';

    public function label(): string
    {
        return match ($this) {
            self::Satb => 'SATB',
            self::SopranoAlto => 'Soprano/Alto',
            self::TenorBass => 'Tenor/Bass',
            self::Unknown => 'Unknown',
        };
    }
}
