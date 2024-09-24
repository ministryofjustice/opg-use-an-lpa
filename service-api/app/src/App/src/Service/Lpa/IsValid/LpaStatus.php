<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

enum LpaStatus: string
{
    case REGISTERED = 'registered';
    case CANCELLED  = 'cancelled';
}
