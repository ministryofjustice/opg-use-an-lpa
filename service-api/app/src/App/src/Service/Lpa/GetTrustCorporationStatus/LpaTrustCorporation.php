<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

use JsonSerializable;

class LpaTrustCorporation implements JsonSerializable
{
    /**
     * @param array|object $actor
     * @param ActorType $actorType
     */
    public function __construct(
        public readonly mixed $trustCorporation,
        public readonly TrustCorporation $actorType,
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