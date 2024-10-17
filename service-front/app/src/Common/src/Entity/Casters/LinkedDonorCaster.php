<?php

declare(strict_types=1);

namespace Common\Entity\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

#[Attribute(Attribute::TARGET_PARAMETER)]
class LinkedDonorCaster implements PropertyCaster
{
    /**
     * @throws UnableToHydrateObject
     */
    public function cast(mixed $value, ObjectMapper $hydrator): array
    {
        $linkedDonors = [];

        foreach ($value as $linked) {
            $linkedDonors[] = [
                   'id'  => $linked['id'],
                   'uId' => $linked['uId'],
               ];
        }

        return $linkedDonors;
    }
}
