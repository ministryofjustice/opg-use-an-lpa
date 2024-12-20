<?php

declare(strict_types=1);

namespace App\Enum;

enum LpaType: string
{
    case PERSONAL_WELFARE     = 'hw';
    case PROPERTY_AND_AFFAIRS = 'pfa';
}
