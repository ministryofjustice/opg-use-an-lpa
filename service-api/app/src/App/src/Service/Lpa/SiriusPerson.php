<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\AccessForAll\AddAccessForAllActorInterface;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;
use ArrayAccess;
use Exception;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template-implements ArrayAccess<array-key, string|array>
 * @template-implements IteratorAggregate<array-key, string|array>
 */
class SiriusPerson implements
    AddAccessForAllActorInterface,
    GetTrustCorporationStatusInterface,
    GetAttorneyStatusInterface,
    ActorMatchingInterface,
    ArrayAccess,
    IteratorAggregate,
    JsonSerializable
{
    public function __construct(private array $person)
    {
    }

    public function getFirstname(): string
    {
        return (string)$this->person['firstname'];
    }

    public function getFirstnames(): string
    {
        /**
         * Although technically we should be doing
         *
         *     trim(sprintf('%s %s', $this->person['firstname'], $this->person['middlenames']));
         *
         * to produce an accurate "firstNames" field the interim object and current frontend code
         * are not setup to handle that so we'll just return the firstname field here.
         */
        return $this->getFirstname();
    }

    public function getMiddleNames(): string
    {
        return (string)$this->person['middlenames'];
    }

    public function getSurname(): string
    {
        return (string)$this->person['surname'];
    }

    public function getStatus(): bool
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

    /**
     * @throws Exception
     */
    public function getDob(): DateTimeInterface
    {
        if (is_null($this->person['dob'])) {
            throw new Exception('Actor DOB is not set');
        }

        return new DateTimeImmutable($this->person['dob']);
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
