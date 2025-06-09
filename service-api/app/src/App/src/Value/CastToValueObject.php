<?php

declare(strict_types=1);

namespace App\Value;

use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

/**
 * @template T
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class CastToValueObject implements PropertyCaster
{
    /**
     * @param class-string<T> $classNameToCast
     */
    public function __construct(
        public readonly string $classNameToCast,
    ) {
    }

    /**
     * @param mixed        $value
     * @param ObjectMapper $hydrator
     * @return T
     */
    public function cast(mixed $value, ObjectMapper $hydrator): object
    {
        return new $this->classNameToCast($value);
    }
}
