<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\Entity\Lpa;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use JsonSerializable;

class ValidatedActorCode implements JsonSerializable
{
    public function __construct(
        public readonly LpaActor $actor,
        public readonly Lpa|SiriusLpa $lpa,
        public readonly bool $hasPaperVerificationCode,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'actor' => $this->actor,
            'lpa'   => $this->lpa,
        ];
    }
}
