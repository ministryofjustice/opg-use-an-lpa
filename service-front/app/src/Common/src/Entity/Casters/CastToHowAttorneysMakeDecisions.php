<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Attribute;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\WhenTheLpaCanBeUsed;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToHowAttorneysMakeDecisions implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        return HowAttorneysMakeDecisions::from($value)->value;
    }
}
