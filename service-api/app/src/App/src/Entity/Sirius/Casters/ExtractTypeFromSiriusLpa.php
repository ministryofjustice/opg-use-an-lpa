<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractTypeFromSiriusLpa implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value[0]['type'])) {
            return $value[0]['type'];
        }

        return null;
    }
}