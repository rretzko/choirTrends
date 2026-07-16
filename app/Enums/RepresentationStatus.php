<?php

declare(strict_types=1);

namespace App\Enums;

enum RepresentationStatus: string
{
    case PendingReview = 'PendingReview';
    case Approved = 'Approved';
    case Rejected = 'Rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
