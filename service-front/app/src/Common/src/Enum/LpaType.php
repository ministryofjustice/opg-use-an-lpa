<?php

declare(strict_types=1);

namespace Common\Enum;

use InvalidArgumentException;

enum LpaType: string
{
    case PERSONAL_WELFARE     = 'hw';
    case PROPERTY_AND_AFFAIRS = 'pfa';

    public static function fromShortName(string $shortName): self
    {
        return match ($shortName) {
            'personal-welfare' => self::PERSONAL_WELFARE,
            'property-and-affairs' => self::PROPERTY_AND_AFFAIRS,
            default => throw new InvalidArgumentException('Invalid shorthand name: ' . $shortName),
        };
    }

    public function isPersonalWelfare(): bool
    {
        return $this === self::PERSONAL_WELFARE;
    }

    public function isPropertyAndAffairs(): bool
    {
        return $this === self::PROPERTY_AND_AFFAIRS;
    }
}
