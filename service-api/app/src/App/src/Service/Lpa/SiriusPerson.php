<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\ActorDateOfBirthNotSetException;
use App\Service\Lpa\AccessForAll\AddAccessForAllActorInterface;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;
use App\Service\Lpa\LpaAlreadyAdded\DonorInformationInterface;
use App\Service\Lpa\LpaRemoved\LpaRemovedDonorInformationInterface;
use ArrayAccess;
use Exception;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use JsonSerializable;
use Psr\Log\LoggerInterface;
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
    DonorInformationInterface,
    LpaRemovedDonorInformationInterface,
    ArrayAccess,
    IteratorAggregate,
    JsonSerializable
{
    public function __construct(private array $person, private LoggerInterface $logger)
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
            throw new ActorDateOfBirthNotSetException('Actor DOB is not set');
        }

        return new DateTimeImmutable($this->person['dob']);
    }

    public function offsetExists(mixed $offset): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusPerson object as array (exists) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

        return isset($this->person[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusPerson object as array (getter) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

        return $this->person[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusPerson object as array (setter) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

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
