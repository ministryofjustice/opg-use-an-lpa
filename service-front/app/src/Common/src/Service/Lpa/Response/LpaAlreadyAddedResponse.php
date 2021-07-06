<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

class LpaAlreadyAddedResponse
{
    /** @var string */
    protected $donorName;

    /** @var string */
    protected $caseSubtype;

    /** @var string */
    protected $lpaActorToken;

    public function getDonorName(): ?string
    {
        return $this->donorName;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function getLpaActorToken(): ?string
    {
        return $this->lpaActorToken;
    }

    public function setDonorName(string $donorName): void
    {
        $this->donorName = $donorName;
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
