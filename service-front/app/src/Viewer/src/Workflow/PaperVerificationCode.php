<?php

declare(strict_types=1);

namespace Viewer\Workflow;

use Common\Entity\Code;
use Common\Workflow\Traits\JsonSerializable;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use DateTimeInterface;

class PaperVerificationCode implements WorkflowState
{
    use JsonSerializable;

    public ?DateTimeInterface $dateOfBirth;
    public ?Code $code;

    public function __construct(
        public ?string $lastName = null,
        mixed $code = null,
        public ?string $lpaUid = null,
        public ?bool $sentToDonor = null,
        public ?string $attorneyName = null,
        ?string $dateOfBirth = null,
        public ?int $noOfAttorneys = null,
        public ?string $organisation = null,
        public ?string $donorName = null,
        public ?string $lpaType = null,
    ) {
        $this->code        = is_array($code) ? new Code($code['value']) : $code;
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
        $this->organisation  = null;
        $this->donorName     = null;
        $this->lpaType       = null;
    }
}
