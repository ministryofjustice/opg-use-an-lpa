<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractAddressLine2FromSiriusLpa implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value[0]['addressLine2'])) {
            return $value[0]['addressLine2'];
        }

        return null;
    }
}
