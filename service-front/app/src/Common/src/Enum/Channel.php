<?php

declare(strict_types=1);

namespace Common\Enum;

enum Channel: string
{
    case PAPER  = 'paper';
    case ONLINE = 'online';

    public function isPaperChannel(): bool
    {
        return $this === self::PAPER;
    }

    public function isOnlineChannel(): bool
    {
        return $this === self::ONLINE;
    }
}