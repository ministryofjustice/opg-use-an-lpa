<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

use function assert;
use function is_int;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class CastToDateTimeImmutable implements PropertyCaster
{
    public function __construct(private ?string $format = null, private ?string $timeZone = null)
    {
    }

    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        $timeZone = $this->timeZone ? new DateTimeZone($this->timeZone) : $this->timeZone;

        if ($this->format !== null) {
            return DateTimeImmutable::createFromFormat($this->format, $value, $timeZone);
        }

        return new DateTimeImmutable($value, $timeZone);
    }
}
