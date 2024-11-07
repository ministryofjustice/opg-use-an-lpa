<?php

namespace Common\Enum;

enum WhenTheLpaCanBeUsed: string
{
    case WHEN_CAPACITY_LOST                              = 'when-capacity-lost';
    case WHEN_HAS_CAPACITY                               = 'when-has-capacity';
    case UNKNOWN                                         = '';

    public function isWhenCapacityLost(): bool
    {
        return $this === WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST;
    }

    public function isWhenHasCapacity(): bool
    {
        return $this === WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY;
    }

    public function isUnknown(): bool
    {
        return $this === WhenTheLpaCanBeUsed::UNKNOWN;
    }
}