<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use DateTimeInterface;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertySerializer;

class DateToStringSerializer implements PropertySerializer
{
    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $value;
    }
}
