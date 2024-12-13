<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

enum AttorneyStatus
{
    case ACTIVE_ATTORNEY;
    case GHOST_ATTORNEY;
    case INACTIVE_ATTORNEY;
    case REPLACEMENT_ATTORNEY;
}
