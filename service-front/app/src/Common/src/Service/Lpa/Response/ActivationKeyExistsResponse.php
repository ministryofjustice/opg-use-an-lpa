<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

use Common\Entity\CaseActor;

class ActivationKeyExistsResponse
{
    protected CaseActor $donor;

    protected string $caseSubtype;

    public function getDonor(): ?CaseActor
    {
        return $this->donor;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function setDonor(CaseActor $donor): void
    {
        $this->donor = $donor;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }
}
