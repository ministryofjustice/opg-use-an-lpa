<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Casters\CastToCaseSubtype;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeInterface;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class Lpa
{
    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        #[MapFrom('howAttorneysMakeDecisions')]
        #[CastToWhenTheLpaCanBeUsed]
        public readonly ?HowAttorneysMakeDecisions $attorneyActDecisions,
        #[CastListToType(Attorney::class)]
        public readonly ?array $attorneys,
        #[MapFrom('lpaType')]
        #[CastToCaseSubtype]
        public readonly ?LpaType $caseSubtype,
        public readonly ?string $channel,
        public readonly ?DateTimeInterface $dispatchDate,
        #[CastSingleDonor]
        public readonly ?object $donor,
        public readonly ?bool $hasSeveranceWarning,
        public readonly ?DateTimeInterface $invalidDate,
        #[MapFrom('lifeSustainingTreatmentOption')]
        #[CastToLifeSustainingTreatment]
        public readonly ?LifeSustainingTreatment $lifeSustainingTreatment,
        #[MapFrom('signedAt')]
        public readonly ?DateTimeInterface $lpaDonorSignatureDate,
        public readonly ?bool $lpaIsCleansed,
        public readonly ?string $onlineLpaId,
        public readonly ?DateTimeInterface $receiptDate,
        public readonly ?DateTimeInterface $registrationDate,
        public readonly ?DateTimeInterface $rejectedDate,
        public readonly ?array $replacementAttorneys,
        public readonly ?string $status,
        public readonly ?DateTimeInterface $statusDate,
        #[CastListToType(TrustCorporations::class)]
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        public readonly ?DateTimeInterface $withdrawnDate,
    ) {
    }
}
