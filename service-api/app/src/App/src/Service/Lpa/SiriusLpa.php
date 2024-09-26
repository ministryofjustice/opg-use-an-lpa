<?php

declare(strict_types=1);

namespace App\Service\Lpa;

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
        $this->transformArrayToSiriusPersons('trustCorporations');
    }

    private function getAttorneys(): array
    {
        return $this->lpa['attorneys'];
    }

    private function getDonor(): array
    {
        return $this->lpa['donor'];
    }

    private function transformArrayToSiriusPersons(string $keyName): void
    {
        if (array_key_exists($keyName, $this->lpa)) {
            $this->lpa[$keyName] = array_map(function ($entity) {
                return $this->convertToSiriusPerson($entity);
            }, $this->lpa[$keyName]);
        }
    }

    private function convertToSiriusPerson($entity): SiriusPerson
    {
        return $entity instanceof SiriusPerson
            ? $entity
            : new SiriusPerson($entity);
    }

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

    public function getStatus(): string
    {
        return $this->lpa['status'];
    }

    public function getUid(): string
    {
        return $this->lpa['uId'];
    }
}
