<?php

declare(strict_types=1);

namespace App\DataAccess\ValueObjects;

use DateTimeImmutable as GlobalDateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * Simple wrapper class that ensures json_encode returns a nice string instead
 * of a PHP array.
 */
class DateTimeImmutable extends GlobalDateTimeImmutable implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return $this->format(DateTimeInterface::ATOM);
    }
}
