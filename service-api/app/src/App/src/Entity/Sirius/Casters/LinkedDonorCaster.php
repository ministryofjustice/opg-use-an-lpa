<?php

declare(strict_types=1);

namespace App\Entity\Sirius\Casters;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

#[Attribute(Attribute::TARGET_PARAMETER)]
class LinkedDonorCaster implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): array
    {
        $linkedDonors = [];

        foreach ($value as $linked) {
            $linkedDonors[] = [
                'id'  => $linked['id'],
                'uId' => str_replace('-', '', $linked['uId']),
            ];
        }

        return $linkedDonors;
    }
}
