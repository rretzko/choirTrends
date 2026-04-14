<?php

declare(strict_types=1);

namespace App\Enums;

enum SurveyReason: string
{
    case NoTime = 'no_time';
    case NotSure = 'not_sure';
    case NoDigitalCopy = 'no_digital_copy';
    case JustBrowsing = 'just_browsing';
    case DoesntMeetNeeds = 'doesnt_meet_needs';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NoTime => "I haven't had time yet",
            self::NotSure => "I'm not sure how to upload a program",
            self::NoDigitalCopy => "I don't have a digital copy of my program",
            self::JustBrowsing => "I'm just browsing / exploring the site",
            self::DoesntMeetNeeds => "The site doesn't meet my needs",
            self::Other => 'Other',
        };
    }
}
