<?php

declare(strict_types=1);

namespace App\Enums;

enum RepresentationSourceType: string
{
    case SelfIdentified = 'SelfIdentified';
    case PublisherBio = 'PublisherBio';
    case ThirdPartyDirectory = 'ThirdPartyDirectory';
    case StaffResearch = 'StaffResearch';

    public function label(): string
    {
        return match ($this) {
            self::SelfIdentified => 'Self-identified',
            self::PublisherBio => 'Publisher bio',
            self::ThirdPartyDirectory => 'Third-party directory',
            self::StaffResearch => 'Staff research',
        };
    }
}
