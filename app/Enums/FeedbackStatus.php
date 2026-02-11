<?php

declare(strict_types=1);

namespace App\Enums;

enum FeedbackStatus: string
{
    case Open = 'Open';
    case Pending = 'Pending';
    case Wip = 'Wip';
    case Closed = 'Closed';
}
