<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

enum ActorType: string
{
    case ATTORNEY          = 'attorney';
    case DONOR             = 'donor';
    case TRUST_CORPORATION = 'trust-corporation';
}
