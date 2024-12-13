<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use App\Enum\HowAttorneysMakeDecisions;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToAttorneyActDecisions implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        return HowAttorneysMakeDecisions::from($value)->value;
    }
}
