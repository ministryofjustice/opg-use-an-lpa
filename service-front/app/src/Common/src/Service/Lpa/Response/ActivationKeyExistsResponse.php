<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

class ActivationKeyExistsResponse
{
    /** @var string */
    protected $donorName = null;

    /** @var string */
    protected $caseSubtype = null;

    public function getDonorName(): ?string
    {
        return $this->donorName;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function setDonorName(string $donorName): void
    {
        $this->donorName = $donorName;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }
}
