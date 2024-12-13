<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use App\Enum\ActorStatus;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToSiriusActorStatus implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): string
    {
        return $value === true ? ActorStatus::ACTIVE->value : ActorStatus::INACTIVE->value;
    }
}
