<?php

declare(strict_types=1);

namespace App\Enums;

enum SchoolType: string
{
    case HighSchool = 'HighSchool';
    case MiddleSchool = 'MiddleSchool';
    case ElementarySchool = 'ElementarySchool';
    case CommunityChoir = 'CommunityChoir';
    case ChurchChoir = 'ChurchChoir';
    case University = 'UniversityChoir';
    case Honors = 'HonorsChoir';

    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::HighSchool => 'High School',
            self::MiddleSchool => 'Middle School',
            self::ElementarySchool => 'Elementary School',
            self::CommunityChoir => 'Community Choir',
            self::ChurchChoir => 'Church Choir',
            self::University => 'University Choir',
            self::Honors => 'Honors Choir',
            self::Other => 'Other',
        };
    }
}
