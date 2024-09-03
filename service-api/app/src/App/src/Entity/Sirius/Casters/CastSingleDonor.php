<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use App\Entity\LpaStore\LpaStoreDonor;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastSingleDonor implements PropertyCaster
{
    /**
     * @throws UnableToHydrateObject
     */
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        return $hydrator->hydrateObject(LpaStoreDonor::class, $value);
    }
}
