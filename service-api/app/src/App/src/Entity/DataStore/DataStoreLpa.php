<?php

declare(strict_types=1);

namespace App\Entity\DataStore;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\Casters\CastToCaseSubtype;
use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class DataStoreLpa extends Lpa
{
    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[MapFrom('howAttorneysMakeDecisions')]
        #[CastToWhenTheLpaCanBeUsed]
        ?HowAttorneysMakeDecisions $attorneyActDecisions,
        #[CastListToType(DataStoreAttorney::class)]
        ?array $attorneys,
        #[MapFrom('lpaType')]
        #[CastToCaseSubtype]
        ?LpaType $caseSubtype,
        ?string $channel,
        ?DateTimeImmutable $dispatchDate,
        #[CastSingleDonor]
        ?object $donor,
        ?bool $hasSeveranceWarning,
        ?DateTimeImmutable $invalidDate,
        #[MapFrom('lifeSustainingTreatmentOption')]
        #[CastToLifeSustainingTreatment]
        ?LifeSustainingTreatment $lifeSustainingTreatment,
        #[MapFrom('signedAt')]
        ?DateTimeImmutable $lpaDonorSignatureDate,
        ?bool $lpaIsCleansed,
        ?string $onlineLpaId,
        ?DateTimeImmutable $receiptDate,
        ?DateTimeImmutable $registrationDate,
        ?DateTimeImmutable $rejectedDate,
        ?array $replacementAttorneys,
        ?string $status,
        ?DateTimeImmutable $statusDate,
        #[CastListToType(DataStoreTrustCorporations::class)]
        ?array $trustCorporations,
        ?string $uId,
        ?DateTimeImmutable $withdrawnDate,
    ) {
        parent::__construct(
            $applicationHasGuidance,
            $applicationHasRestrictions,
            $applicationType,
            $attorneyActDecisions,
            $attorneys,
            $caseSubtype,
            $channel,
            $dispatchDate,
            $donor,
            $hasSeveranceWarning,
            $invalidDate,
            $lifeSustainingTreatment,
            $lpaDonorSignatureDate,
            $lpaIsCleansed,
            $onlineLpaId,
            $receiptDate,
            $registrationDate,
            $rejectedDate,
            $replacementAttorneys,
            $status,
            $statusDate,
            $trustCorporations,
            $uId,
            $withdrawnDate
        );
    }
}
