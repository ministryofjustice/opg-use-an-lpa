<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

use Common\Entity\CaseActor;
use DateTimeInterface;

class ActivationKeyExists extends Response
{
    protected CaseActor $donor;
    protected string $caseSubtype;
    protected ?DateTimeInterface $activationKeyDueDate;
    protected ?DateTimeInterface $activationKeyRequestedDate;

    public function getDonor(): ?CaseActor
    {
        return $this->donor;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->activationKeyDueDate;
    }

    public function getRequestedDate(): ?DateTimeInterface
    {
        return $this->activationKeyRequestedDate;
    }

    public function setDonor(CaseActor $donor): void
    {
        $this->donor = $donor;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }

    public function setDueDate(DateTimeInterface $activationKeyDueDate): void
    {
        $this->activationKeyDueDate = $activationKeyDueDate;
    }

    public function setRequestedDate(DateTimeInterface $activationKeyRequestedDate): void
    {
        $this->activationKeyRequestedDate = $activationKeyRequestedDate;
    }
}
