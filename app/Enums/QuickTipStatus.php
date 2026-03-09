<?php

declare(strict_types=1);

namespace App\Enums;

enum QuickTipStatus: string
{
    case Draft = 'Draft';
    case Pending = 'Pending';
    case Sent = 'Sent';

    public function label(): string
    {
        return $this->value;
    }
}
