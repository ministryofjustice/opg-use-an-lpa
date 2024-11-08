<?php

declare(strict_types=1);

namespace App\Enum;

enum WhenTheLpaCanBeUsed: string
{
    case WHEN_CAPACITY_LOST                              = 'when-capacity-lost';
    case WHEN_HAS_CAPACITY                               = 'when-has-capacity';
    case UNKNOWN                               = '';
}
