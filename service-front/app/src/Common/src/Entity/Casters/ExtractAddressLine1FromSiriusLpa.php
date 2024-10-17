<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractAddressLine1FromSiriusLpa implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value[0]['addressLine1'])) {
            return $value[0]['addressLine1'];
        }

        return null;
    }
}
