<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

use JsonSerializable;

class LpaActor implements JsonSerializable
{
    /**
     * @param array|object $actor
     * @param ActorType $actorType
     */
    public function __construct(
        public readonly mixed $actor,
        public readonly ActorType $actorType,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'details' => $this->actor,
            'type'    => $this->actorType->value,
        ];
    }
}
