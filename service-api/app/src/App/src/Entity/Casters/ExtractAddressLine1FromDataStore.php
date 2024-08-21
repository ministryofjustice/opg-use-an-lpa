<?php

namespace App\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractAddressLine1FromDataStore implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value['line1'])) {
            return $value['line1'];
        }

        return null;
    }
}