<?php

declare(strict_types=1);

namespace Common\Enum;

enum Channel: string
{
    case PAPER  = 'paper';
    case ONLINE = 'online';
}
