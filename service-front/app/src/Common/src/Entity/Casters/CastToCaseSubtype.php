<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Common\Enum\LpaType;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToCaseSubtype implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): string
    {
        return LpaType::fromShortName($value)->value;
    }
}