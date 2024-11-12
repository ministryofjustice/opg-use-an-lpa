<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\FindActorInLpa\FindActorInLpaInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\ResolveActor\ResolveActorInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use App\Entity\Sirius\Casters\CastToSiriusLifeSustainingTreatment;
use Exception;

class SiriusLpa extends Lpa implements FindActorInLpaInterface
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
        ?SiriusLpaDonor $donor,
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
        #[CastListToType(SiriusLpaTrustCorporation::class)]
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

    public function getTrustCorporations(): array
    {
        return $this->trustCorporations ?? [];
    }

    public function getDonor(): ActorMatchingInterface&GetAttorneyStatusInterface&ResolveActorInterface
    {
        if (
            !(
                $this->donor instanceof ActorMatchingInterface &&
                $this->donor instanceof GetAttorneyStatusInterface &&
                $this->donor instanceof ResolveActorInterface
            )
        ) {
            throw new Exception(
                'Donor is not a valid ActorMatchingInterface&GetAttorneyStatusInterface instance'
            );
        }

        return $this->donor;
    }
}
