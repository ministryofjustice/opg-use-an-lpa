<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ExtractCountryFromLpaStore implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_array($value) && isset($value['country'])) {
            return $value['country'];
        }

        return null;
    }
}
