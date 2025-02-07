<?php

declare(strict_types=1);

namespace Common\Enum;

enum LifeSustainingTreatment: string
{
    case OPTION_A = 'option-a';
    case OPTION_B = 'option-b';

    public function isOptionA(): bool
    {
        return $this === self::OPTION_A;
    }

    public function isOptionB(): bool
    {
        return $this === self::OPTION_B;
    }
}
