<?php

declare(strict_types=1);

namespace App\Enum;

enum LpaStatus: string
{
    case REGISTERED = 'registered';
    case CANCELLED  = 'cancelled';
}
