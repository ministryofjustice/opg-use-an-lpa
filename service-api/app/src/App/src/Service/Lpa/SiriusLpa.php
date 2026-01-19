<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\AccessForAll\AddAccessForAllLpaInterface;
use App\Service\Lpa\AddLpa\AddLpaInterface;
use App\Service\Lpa\Combined\FilterActiveActorsInterface;
use App\Service\Lpa\FindActorInLpa\FindActorInLpaInterface;
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\LpaAlreadyAdded\LpaAlreadyAddedInterface;
use App\Service\Lpa\LpaRemoved\LpaRemovedInterface;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\SiriusHasActorTrait;
use App\Service\Lpa\RestrictSendingLpaForCleansing\RestrictSendingLpaForCleansingInterface;
use ArrayAccess;
use DateTimeInterface;
use DateTimeImmutable;
use IteratorAggregate;
use JsonSerializable;
use Psr\Log\LoggerInterface;
use Traversable;

/**
 * @template-implements ArrayAccess<array-key, string|array>
 * @template-implements IteratorAggregate<array-key, string|array>
 */
class SiriusLpa implements
    AddAccessForAllLpaInterface,
    HasActorInterface,
    FindActorInLpaInterface,
    IsValidInterface,
    LpaAlreadyAddedInterface,
    LpaRemovedInterface,
    AddLpaInterface,
    RestrictSendingLpaForCleansingInterface,
    FilterActiveActorsInterface,
    HasRestrictionsInterface,
    ArrayAccess,
    IteratorAggregate,
    JsonSerializable
{
    use SiriusHasActorTrait;

    public function __construct(private array $lpa, private LoggerInterface $logger)
    {
        if ($this->lpa['donor'] !== null) {
            $donorAsSiriusPerson = $this->convertToSiriusPerson($this->lpa['donor']);
            $this->lpa['donor']  = $donorAsSiriusPerson;
        }

        $this->transformArrayToSiriusPersons('attorneys');
        $this->transformArrayToSiriusPersons('trustCorporations');
    }

    /**
     * @return SiriusPerson[]
     */
    public function getAttorneys(): array
    {
        /** @var SiriusPerson[] */
        return $this->lpa['attorneys'];
    }

    public function getDonor(): SiriusPerson
    {
        return $this->lpa['donor'];
    }

    public function getUid(): string
    {
        return (string)$this->lpa['uId'];
    }

    private function transformArrayToSiriusPersons(string $keyName): void
    {
        if (array_key_exists($keyName, $this->lpa)) {
            $this->lpa[$keyName] = array_map(function (SiriusPerson|array $entity) {
                return $this->convertToSiriusPerson($entity);
            }, $this->lpa[$keyName]);
        }
    }

    private function convertToSiriusPerson(SiriusPerson|array $entity): SiriusPerson
    {
        return $entity instanceof SiriusPerson
            ? $entity
            : new SiriusPerson($entity, $this->logger);
    }

    /**
     * @return SiriusPerson[]
     */
    public function getTrustCorporations(): array
    {
        /** @var SiriusPerson[] */
        return $this->lpa['trustCorporations'];
    }

    public function offsetExists(mixed $offset): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusLpa object as array (exists) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

        return isset($this->lpa[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusLpa object as array (getter) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

        return $this->lpa[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logger->debug(
            'Use of SiriusLpa object as array (setter) in file '
            . $trace[0]['file'] . ' on line ' . $trace[0]['line']
        );

        $this->lpa[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->lpa[$offset]);
    }

    public function getIterator(): Traversable
    {
        yield from $this->lpa;
    }

    public function toArray(): array
    {
        return $this->lpa;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getStatus(): string
    {
        return (string)$this->lpa['status'];
    }

    public function getCaseSubType(): string
    {
        return $this['caseSubtype'] ?? '';
    }

    public function getRegistrationDate(): DateTimeInterface
    {
        return new DateTimeImmutable($this->lpa['registrationDate']);
    }

    public function getLpaIsCleansed(): bool
    {
        return $this->lpa['lpaIsCleansed'];
    }

    /**
     * @inheritDoc
     */
    public function withAttorneys(array $attorneys): self
    {
        $this->lpa['attorneys'] = $attorneys;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withTrustCorporations(array $trustCorporations): self
    {
        $this->lpa['trustCorporations'] = $trustCorporations;
        return $this;
    }

    public function hasGuidance(): bool
    {
        return $this->lpa['applicationHasGuidance'] ?? false;
    }

    public function hasRestrictions(): bool
    {
        return $this->lpa['applicationHasRestrictions'] ?? false;
    }
}
