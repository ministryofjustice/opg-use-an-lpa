<?php

declare(strict_types=1);

namespace Common\Entity\Sirius;

use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use App\Entity\Sirius\Casters\CastSiriusDonor;
use App\Entity\Sirius\Casters\CastToSiriusLifeSustainingTreatment;

class SiriusLpa extends Lpa
{
    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[CastToWhenTheLpaCanBeUsed]
        ?HowAttorneysMakeDecisions $attorneyActDecisions,
        #[CastListToType(SiriusLpaAttorney::class)]
        ?array $attorneys,
        ?LpaType $caseSubtype,
        ?string $channel,
        ?DateTimeImmutable $dispatchDate,
        #[CastSiriusDonor]
        ?object $donor,
        ?bool $hasSeveranceWarning,
        ?DateTimeImmutable $invalidDate,
        #[CastToSiriusLifeSustainingTreatment]
        ?LifeSustainingTreatment $lifeSustainingTreatment,
        ?DateTimeImmutable $lpaDonorSignatureDate,
        ?bool $lpaIsCleansed,
        ?string $onlineLpaId,
        ?DateTimeImmutable $receiptDate,
        ?DateTimeImmutable $registrationDate,
        ?DateTimeImmutable $rejectedDate,
        #[CastListToType(SiriusLpaAttorney::class)]
        ?array $replacementAttorneys,
        ?string $status,
        ?DateTimeImmutable $statusDate,
        #[CastListToType(SiriusLpaTrustCorporations::class)]
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
