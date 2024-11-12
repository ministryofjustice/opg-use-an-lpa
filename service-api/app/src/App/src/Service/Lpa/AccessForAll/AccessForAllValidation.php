<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use App\Entity\Lpa;
use App\Entity\Person;
use App\Exception\InvalidActorTypeException;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\SiriusLpa;
use JsonSerializable;

class AccessForAllValidation implements JsonSerializable
{
    public function __construct(
        public readonly ActorMatch $actorMatch,
        public readonly SiriusLpa|Lpa $lpa,
        public readonly ?string $lpaActorToken = null,
    ) {
        if (! $this->actorMatch->actor instanceof ActorMatchingInterface) {
            throw new InvalidActorTypeException(
                'Given ActorMatch actor must implement ' . ActorMatchingInterface::class
            );
        }
    }

    public function getCaseSubtype(): string
    {
        return $this->lpa['caseSubtype'];
    }

    public function jsonSerialize(): array
    {
        $data = [
            'actor'  => $this->actorMatch->actor,
            'role'   => $this->actorMatch->role,
            'lpa-id' => $this->actorMatch->uid,
        ];

        if ($this->actorMatch->role === 'attorney') {
            $data['attorney'] = [
                'uId'         => $this->actorMatch->actor->getUid(),
                'firstname'   => $this->actorMatch->actor->getFirstnames(),
                'middlenames' => $this->actorMatch->actor->getMiddleNames(),
                'surname'     => $this->actorMatch->actor->getSurname(),
            ];
        }

        if ($this->lpaActorToken !== null) {
            $data['lpaActorToken'] = $this->lpaActorToken;
        }

        $data['caseSubtype'] = $this->getCaseSubtype();
        $data['donor']       = [
            'uId'         => $this->lpa->getDonor()->getUId(),
            'firstname'   => $this->lpa->getDonor()->getFirstnames(),
            'middlenames' => $this->lpa->getDonor()->getMiddleNames(),
            'surname'     => $this->lpa->getDonor()->getSurname(),
        ];

        return $data;
    }
}
