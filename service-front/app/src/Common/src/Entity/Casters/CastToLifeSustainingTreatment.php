<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Common\Enum\LifeSustainingTreatment;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToLifeSustainingTreatment implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        return LifeSustainingTreatment::from($value)->value;
    }
}
