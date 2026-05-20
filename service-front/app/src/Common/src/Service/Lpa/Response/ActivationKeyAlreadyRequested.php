<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

use Common\Entity\CaseActor;

final class ActivationKeyAlreadyRequested extends Response
{
    public function __construct(
        private string $addedDate,
        private CaseActor $donor,
        private string $caseSubtype,
        private string $activationKeyDueDate,
    ) {
    }

    public function getAddedDate(): string
    {
        return $this->addedDate;
    }

    public function getDonor(): CaseActor
    {
        return $this->donor;
    }

    public function getCaseSubtype(): string
    {
        return $this->caseSubtype;
    }

    public function getDueDate(): string
    {
        return $this->activationKeyDueDate;
    }
}
