<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

enum TrustCorporationStatuses: int
{
    case ACTIVE_TC   = 0;
    case GHOST_TC    = 1;
    case INACTIVE_TC = 2;
}
