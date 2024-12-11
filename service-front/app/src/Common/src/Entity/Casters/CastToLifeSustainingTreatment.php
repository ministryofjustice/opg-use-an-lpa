<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Common\Enum\LifeSustainingTreatment;
use Attribute;
use Common\Enum\LpaType;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToLifeSustainingTreatment implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        if (is_null(LifeSustainingTreatment::tryFrom($value))) {
            $value = match ($value) {
                'Option A' => LifeSustainingTreatment::OPTION_A,
                'Option B' => LifeSustainingTreatment::OPTION_B,
                default => throw new InvalidArgumentException('Invalid shorthand name: ' . $value),
            };
        }

        return LifeSustainingTreatment::from($value)->value;
    }
}
