<?php

declare(strict_types=1);

namespace App\Service\Lpa\FindActorInLpa;

use App\Entity\Person;
use App\Service\Lpa\SiriusPerson;
use JsonSerializable;

class ActorMatch implements JsonSerializable
{
    public function __construct(
        public readonly ActorMatchingInterface $actor,
        public readonly string $role,
        public readonly string $lpaUId,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'actor'   => $this->actor,
            'role'    => $this->role,
            'lpa-uid' => $this->lpaUId,
        ];
    }
}
