<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use App\Entity\Sirius\SiriusLpaDonor;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastSiriusDonor implements PropertyCaster
{
    /**
     * @throws UnableToHydrateObject
     */
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        return $hydrator->hydrateObject(SiriusLpaDonor::class, $value);
    }
}
