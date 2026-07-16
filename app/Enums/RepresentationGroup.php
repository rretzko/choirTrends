<?php

declare(strict_types=1);

namespace App\Enums;

enum RepresentationGroup: string
{
    case Gender = 'Gender';
    case RaceEthnicity = 'RaceEthnicity';
    case Sexuality = 'Sexuality';
    case Disability = 'Disability';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Gender => 'Gender',
            self::RaceEthnicity => 'Race / Ethnicity',
            self::Sexuality => 'Sexuality',
            self::Disability => 'Disability',
            self::Other => 'Other',
        };
    }
}
