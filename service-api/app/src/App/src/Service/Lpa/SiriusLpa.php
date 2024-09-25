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
        if ($this->lpa['donor'] !== null) {
            $donorAsSiriusPerson = new SiriusPerson($this->lpa['donor']);
            $this->lpa['donor'] = $donorAsSiriusPerson;
        }

        if (array_key_exists('attorneys', $this->lpa)) {
            $this->convertToSiriusPersons($this->lpa['attorneys']);
        }

        if (array_key_exists('original_attorneys',$this->lpa)) {
            $this->convertToSiriusPersons($this->lpa['original_attorneys']);
        }

        if (array_key_exists('inactiveAttorneys',$this->lpa)) {
            $this->convertToSiriusPersons($this->lpa['inactiveAttorneys']);
        }

        if (array_key_exists('activeAttorneys',$this->lpa)) {
            $this->convertToSiriusPersons($this->lpa['activeAttorneys']);
        }
        //$this->convertToSiriusPersons($this->lpa['trustCorporations'])
    }

    private function convertToSiriusPersons(array $persons): array
    {
        $personAsSiriusPersons = [];
        $index = 0;
        foreach ($persons as $person) {
            if ($person instanceof SiriusPerson) {
                $personsAsSiriusPersons[$index] = $person;
            }
            else {
                $personsAsSiriusPersons[$index] = new SiriusPerson($person);
            }
            $index++;
        }
        return $personAsSiriusPersons;
    }

    private function getAttorneys(): array
    {
        return $this->lpa['attorneys'];
    }

    private function getDonor(): SiriusPerson
    {
        return $this->lpa['donor'];
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

}
