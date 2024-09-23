<?php

declare(strict_types=1);

namespace App\Enum;

use InvalidArgumentException;

enum LifeSustainingTreatment: string
{
    case OPTION_A = 'option-a';
    case OPTION_B = 'option-b';

    public static function fromShortName(string $shortName): self
    {
        return match ($shortName) {
            'Option A' => self::OPTION_A,
            'Option B' => self::OPTION_B,
            default => throw new InvalidArgumentException('Invalid shorthand name: ' . $shortName),
        };
    }
}
