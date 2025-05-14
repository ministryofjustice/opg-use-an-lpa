<?php

declare(strict_types=1);

namespace Viewer\Workflow;

use Common\Workflow\Traits\JsonSerializable;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use DateTimeInterface;

class PaperVerificationShareCode implements WorkflowState
{
    use JsonSerializable;

    public ?DateTimeInterface $dateOfBirth;

    public function __construct(
        public ?string $lastName = null,
        public ?string $code = null,
        public ?string $lpaUid = null,
        public ?bool $sentToDonor = null,
        public ?string $attorneyName = null,
        ?string $dateOfBirth = null,
        public ?int $noOfAttorneys = null,
    ) {
        $this->dateOfBirth = $dateOfBirth !== null ? new DateTimeImmutable($dateOfBirth) : null;
    }

    public function reset(): void
    {
        $this->lastName      = null;
        $this->code          = null;
        $this->lpaUid        = null;
        $this->sentToDonor   = null;
        $this->attorneyName  = null;
        $this->dateOfBirth   = null;
        $this->noOfAttorneys = null;
    }
}
