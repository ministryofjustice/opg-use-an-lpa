<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

use Common\Entity\CaseActor;

class LpaAlreadyAddedResponse
{
    protected CaseActor $donor;
    protected string $caseSubtype;
    protected string $lpaActorToken;

    public function getDonor(): ?CaseActor
    {
        return $this->donor;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function getLpaActorToken(): ?string
    {
        return $this->lpaActorToken;
    }

    public function setDonor(CaseActor $donor): void
    {
        $this->donor = $donor;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }

    public function setLpaActorToken(string $lpaActorToken): void
    {
        $this->lpaActorToken = $lpaActorToken;
    }
}
