<?php

declare(strict_types=1);

namespace Common\Entity\Sirius;

use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use Common\Entity\CombinedLpa;
use Common\Entity\Person;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Service\Lpa\SortLpasInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use Common\Entity\Casters\CastSiriusDonor;
use Common\Entity\Casters\CastToSiriusLifeSustainingTreatment;

class SiriusLpa extends CombinedLpa implements SortLpasInterface
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

    public function getDonor(): Person
    {
        return $this->donor;
    }

    public function getCaseSubtype(): string
    {
        return $this->caseSubtype->value;
    }
}
