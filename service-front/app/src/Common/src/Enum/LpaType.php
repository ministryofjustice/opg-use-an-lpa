<?php

declare(strict_types=1);

namespace Common\Enum;

enum LpaType: string
{
    case PERSONAL_WELFARE     = 'hw';
    case PROPERTY_AND_AFFAIRS = 'pfa';

    public function isPersonalWelfare(): bool
    {
        return $this === self::PERSONAL_WELFARE;
    }

    public function isPropertyAndAffairs(): bool
    {
        return $this === self::PROPERTY_AND_AFFAIRS;
    }
}
