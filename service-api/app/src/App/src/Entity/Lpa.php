<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeImmutable;

class Lpa
{
    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        public readonly ?HowAttorneysMakeDecisions $attorneyActDecisions,
        public readonly ?array $attorneys,
        public readonly ?LpaType $caseSubtype,
        public readonly ?string $channel,
        public readonly ?DateTimeImmutable $dispatchDate,
        public readonly ?object $donor,
        public readonly ?bool $hasSeveranceWarning,
        public readonly ?DateTimeImmutable $invalidDate,
        public readonly ?LifeSustainingTreatment $lifeSustainingTreatment,
        public readonly ?DateTimeImmutable $lpaDonorSignatureDate,
        public readonly ?bool $lpaIsCleansed,
        public readonly ?string $onlineLpaId,
        public readonly ?DateTimeImmutable $receiptDate,
        public readonly ?DateTimeImmutable $registrationDate,
        public readonly ?DateTimeImmutable $rejectedDate,
        public readonly ?array $replacementAttorneys,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $statusDate,
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        public readonly ?DateTimeImmutable $withdrawnDate,
    ) {
    }
}