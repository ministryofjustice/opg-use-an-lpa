<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use App\Entity\Donor;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastSingleDonor implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        return $hydrator->hydrateObject(Donor::class, $value);
    }
}