<?php

declare(strict_types=1);

namespace App\Enum;

enum LpaSource: string
{
    case SIRIUS   = 'sirius';
    case LPASTORE = 'lpastore';
}
