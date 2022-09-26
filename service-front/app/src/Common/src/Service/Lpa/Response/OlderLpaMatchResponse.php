<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

use Common\Entity\CaseActor;

class OlderLpaMatchResponse
{
    protected CaseActor $donor;

    protected ?CaseActor $attorney = null;
    protected string $caseSubtype;
    protected string $activationKeyDueDate;

    public function getDonor(): ?CaseActor
    {
        return $this->donor;
    }

    public function getAttorney(): ?CaseActor
    {
        return $this->attorney;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function getDueDate(): string
    {
        return $this->activationKeyDueDate;
    }

    public function setDonor(CaseActor $donor): void
    {
        $this->donor = $donor;
    }

    public function setAttorney(CaseActor $attorney): void
    {
        $this->attorney = $attorney;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }

    public function setDueDate(string $activationKeyDueDate): void
    {
        $this->activationKeyDueDate = $activationKeyDueDate;
    }
}
