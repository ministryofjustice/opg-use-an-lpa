<?php

declare(strict_types=1);

namespace Common\Entity\LpaStore;

use Common\Entity\Casters\CastSingleDonor;
use Common\Entity\Casters\CastToCaseSubtype;
use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use Common\Entity\Casters\CastToLifeSustainingTreatment;
use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use Common\Entity\CombinedLpa;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class LpaStore extends CombinedLpa
{
    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[CastListToType(LpaStoreAttorney::class)]
        ?array $attorneys,
        #[MapFrom('lpaType')]
        #[CastToCaseSubtype]
        ?LpaType $caseSubtype,
        ?string $channel,
        ?DateTimeImmutable $dispatchDate,
        #[CastSingleDonor]
        ?object $donor,
        ?bool $hasSeveranceWarning,
        #[MapFrom('howAttorneysMakeDecisions')]
        #[CastToHowAttorneysMakeDecisions]
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions,
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
        #[CastListToType(LpaStoreTrustCorporations::class)]
        ?array $trustCorporations,
        #[MapFrom('uid')]
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
