<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferralSource: string
{
    case Facebook = 'Facebook';
    case FacebookChoirDirector = 'FacebookChoirDirector';
    case WebSearch = 'WebSearch';
    case Referral = 'Referral';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook (general)',
            self::FacebookChoirDirector => 'Facebook: I Am A Choir Director',
            self::WebSearch => 'Web search',
            self::Referral => 'Referral',
            self::Other => 'Other',
        };
    }
}
