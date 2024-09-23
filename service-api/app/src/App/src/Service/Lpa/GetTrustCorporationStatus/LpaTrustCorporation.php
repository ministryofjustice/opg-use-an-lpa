<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\LpaActor;

class LpaTrustCorporation extends LpaActor
{
    /**
     * @param mixed $actor
     */
    public function __construct(mixed $actor) {
        parent::__construct($actor, ActorType::TRUST_CORPORATION);
    }

    public function jsonSerialize(): array
    {
        return [
            'details' => $this->actor,
            'type'    => ActorType::TRUST_CORPORATION->value,
        ];
    }
}