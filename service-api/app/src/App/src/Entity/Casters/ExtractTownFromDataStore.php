<?php

namespace App\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractTownFromDataStore implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $mapper): ?string
    {
        if (is_array($value) && isset($value['town'])) {
            return $value['town'];
        }

        return null;
    }
}