<?php

declare(strict_types=1);

namespace Common\Entity\Sirius;

use Common\Entity\Casters\CastToCaseSubtype;
use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use Common\Entity\CombinedLpa;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use Common\Entity\Casters\CastSiriusDonor;
use Common\Entity\Casters\CastToSiriusLifeSustainingTreatment;

class SiriusLpa extends CombinedLpa
{
    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[CastListToType(SiriusLpaAttorney::class)]
        ?array $attorneys,
        #[CastToCaseSubtype]
        ?LpaType $caseSubtype,
        ?string $channel,
        ?DateTimeImmutable $dispatchDate,
        #[CastSiriusDonor]
        ?object $donor,
        ?bool $hasSeveranceWarning,
        #[CastToHowAttorneysMakeDecisions]
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions,
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
        #[MapFrom('whenTheLpaCanBeUsed')]
        #[CastToWhenTheLpaCanBeUsed]
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
    ) {
        parent::__construct(
            $applicationHasGuidance,
            $applicationHasRestrictions,
            $applicationType,
            $attorneys,
            $caseSubtype,
            $channel,
            $dispatchDate,
            $donor,
            $hasSeveranceWarning,
            $howAttorneysMakeDecisions,
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
            $withdrawnDate,
            $whenTheLpaCanBeUsed
        );
    }
}
