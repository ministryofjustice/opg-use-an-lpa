<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use App\Enum\LifeSustainingTreatment;
use Attribute;
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
                'Option A' => LifeSustainingTreatment::OPTION_A->value,
                'Option B' => LifeSustainingTreatment::OPTION_B->value,
                default => throw new InvalidArgumentException('Invalid shorthand name: ' . $value),
            };
        }

        return LifeSustainingTreatment::from($value)->value;
    }
}
