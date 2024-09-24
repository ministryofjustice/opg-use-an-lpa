<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\IsValid\IsValidInterface;
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
class SiriusLpa implements HasActorInterface, IsValidInterface, ArrayAccess, IteratorAggregate, JsonSerializable
{
    use SiriusHasActorTrait;

    public function __construct(private array $lpa)
    {
    }

    private function getAttorneys(): array
    {
        $attorneys = [];
        $index = 0;
        foreach ($this->lpa['attorneys'] as $attorney) {
            $attorneys[$index] = new SiriusPerson($attorney);
            $index++;
        }
        return $attorneys;
    }

    private function getDonor(): SiriusPerson
    {
        return new SiriusPerson($this->lpa['donor']);
    }

    public function getUid(): string
    {
        return $this->lpa['uId'];
    }

    public function getSystemStatus(): string
    {
        return $this->lpa['systemStatus'];
    }

    private function getTrustCorporations(): array
    {
        $trustCorporations = [];
        $index = 0;
        foreach ($this->lpa['trustCorporations'] as $trustCorporation) {
            $trustCorporations[$index] = new SiriusPerson($trustCorporation);
            $index++;
        }
        return $trustCorporations;
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

    public function getStatus(): string
    {
        return $this->lpa['status'];
    }

    public function getUid(): string
    {
        return $this->lpa['uId'];
    }
}
