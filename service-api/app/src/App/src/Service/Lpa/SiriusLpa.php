<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\SiriusHasActorTrait;
use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template-implements ArrayAccess<array-key, string|array>
 * @template-implements IteratorAggregate<array-key, string|array>
 */
class SiriusLpa implements HasActorInterface, ArrayAccess, IteratorAggregate, JsonSerializable
{
    use SiriusHasActorTrait;

    public function __construct(private array $lpa)
    {
    }

    // TODO iterate over attorneys array, and return array of correspodiong SiriusPersons
    private function getAttorneys(): array
    {
        return $this->lpa['attorneys'];
    }

   // TODO needs to return a SiriusPerson
    private function getDonor(): array
    {
        return $this->lpa['donor'];
    }

    public function getFirstname(): string
    {
        return $this->lpa['firstname'];
    }

    public function getSurname(): string
    {
        return $this->lpa['surname'];
    }

    public function getUid(): string
    {
        return $this->lpa['uId'];
    }

    public function getSystemStatus(): string
    {
        return $this->lpa['systemStatus'];
    }

    // TODO iterate over trustcorporations array, and return array of correspodiong SiriusPersons
    private function getTrustCorporations(): array
    {
        return $this->lpa['trustCorporations'];
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->lpa[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->lpa[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
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
}
