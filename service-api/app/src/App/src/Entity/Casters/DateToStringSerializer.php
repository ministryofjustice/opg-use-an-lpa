<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertySerializer;

class DateToStringSerializer implements PropertySerializer
{
    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        if ($value instanceof DateTimeImmutable) {
            return $value->format('d-m-Y H:i:s');
        }

        return $value;
    }
}
