<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractAddressFieldFrom implements PropertyCaster
{
    public function __construct(public readonly string $fieldName)
    {
    }

    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value[$this->fieldName])) {
            return $value[$this->fieldName];
        }

        // little recursive magic to handle Sirius' multiple address structure.
        if (is_array($value) && is_array($value = array_pop($value))) {
            return $this->cast($value, $hydrator);
        }

        return null;
    }
}
