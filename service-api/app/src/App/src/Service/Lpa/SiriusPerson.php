<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatusInterface;
use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template-implements ArrayAccess<array-key, string|array>
 * @template-implements IteratorAggregate<array-key, string|array>
 */
class SiriusPerson implements TrustCorporationStatusInterface, GetAttorneyStatusInterface, ArrayAccess, IteratorAggregate, JsonSerializable
{
    public function __construct(private array $person)
    {
    }

    public function getFirstname(): string
    {
        return (string)$this->person['firstname'];
    }

    public function getSurname(): string
    {
        return (string)$this->person['surname'];
    }

    public function getSystemStatus(): bool
    {
        return (bool)$this->person['systemStatus'];
    }

    public function getUid(): string
    {
        return (string)$this->person['uId'];
    }

    public function getCompanyName(): string
    {
        return (string)$this->person['companyName'];
    }

    public function getPostcode(): string
    {
        return (string)$this->person['addresses'][0]['postcode'];
    }

    public function getDob(): string
    {
        return (string)$this->person['dob'];
    }
    
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->person[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->person[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->person[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->person[$offset]);
    }

    public function getIterator(): Traversable
    {
        yield from $this->person;
    }

    public function toArray(): array
    {
        return $this->person;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
