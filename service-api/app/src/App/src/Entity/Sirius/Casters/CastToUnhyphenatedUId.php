<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToUnhyphenatedUId implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): string
    {
        return str_replace('-', '', $value);
    }
}
