<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractCountryFromSiriusLpa implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value[0]['country'])) {
            return $value[0]['country'];
        }

        return null;
    }
}