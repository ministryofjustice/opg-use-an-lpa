<?php

declare(strict_types=1);

namespace App\Enum;

enum ActorStatus: string
{
    case ACTIVE      = 'active';
    case INACTIVE    = 'inactive';
    case REPLACEMENT = 'replacement';
    case REMOVED     = 'removed';
}
