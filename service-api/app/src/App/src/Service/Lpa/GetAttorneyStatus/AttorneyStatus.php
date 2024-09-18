<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

enum AttorneyStatus: int
{
    case ACTIVE_ATTORNEY   = 0;
    case GHOST_ATTORNEY    = 1;
    case INACTIVE_ATTORNEY = 2;
}