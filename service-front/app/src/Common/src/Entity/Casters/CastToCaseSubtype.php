<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Common\Enum\LpaType;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToCaseSubtype implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_null(LpaType::tryFrom($value))) {
            $value = match ($value) {
                'personal-welfare'     => LpaType::PERSONAL_WELFARE->value,
                'property-and-affairs' => LpaType::PROPERTY_AND_AFFAIRS->value,
                default                =>
                    throw new InvalidArgumentException('Invalid shorthand name: ' . $value),
            };
        }

        return LpaType::from($value)->value;
    }
}
