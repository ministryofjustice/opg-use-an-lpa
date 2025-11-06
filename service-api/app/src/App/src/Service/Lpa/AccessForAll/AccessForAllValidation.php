<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use App\Entity\Lpa;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\SiriusLpa;
use JsonSerializable;

class AccessForAllValidation implements JsonSerializable
{
    public function __construct(
        public readonly ActorMatch $actorMatch,
        public readonly SiriusLpa|Lpa $lpa,
        public readonly ?string $lpaActorToken = null,
    ) {
    }

    public function getCaseSubtype(): string
    {
        return $this->lpa->getCaseSubtype();
    }

    public function jsonSerialize(): array
    {
        $data = [
            'role'   => $this->actorMatch->role,
            'lpa-id' => $this->actorMatch->lpaUId,
        ];

        if ($this->actorMatch->role === 'attorney') {
            $data['attorney'] = [
                'uId'        => $this->actorMatch->actor->getUid(),
                'firstnames' => $this->actorMatch->actor->getFirstnames(),
                'surname'    => $this->actorMatch->actor->getSurname(),
            ];
        }

        if ($this->lpaActorToken !== null) {
            $data['lpaActorToken'] = $this->lpaActorToken;
        }

        $data['caseSubtype'] = $this->getCaseSubtype();
        $data['donor']       = [
            'uId'        => $this->lpa->getDonor()->getUId(),
            'firstnames' => $this->lpa->getDonor()->getFirstnames(),
            'surname'    => $this->lpa->getDonor()->getSurname(),
        ];

        return $data;
    }
}
