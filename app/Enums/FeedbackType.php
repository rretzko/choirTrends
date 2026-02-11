<?php

declare(strict_types=1);

namespace App\Enums;

enum FeedbackType: string
{
    case Bug = 'Bug';
    case Enhancement = 'Enhancement';
    case Kudo = 'Kudo';
    case Comment = 'Comment';
}
