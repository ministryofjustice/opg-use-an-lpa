<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Casters\CastToHowAttorneysMakeDecisions;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\SiriusHasActorTrait;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use App\Entity\Sirius\Casters\CastSiriusDonor;
use App\Entity\Sirius\Casters\CastToSiriusLifeSustainingTreatment;

class SiriusLpa extends Lpa implements HasActorInterface, IsValidInterface
{
    use SiriusHasActorTrait;

    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[CastListToType(SiriusLpaAttorney::class)]
        ?array $attorneys,
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
        #[MapFrom('attorneyActDecisions')]
        #[CastToWhenTheLpaCanBeUsed]
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed
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

    #[DoNotSerialize]
    public function getAttorneys(): array
    {
        return $this->attorneys ?? [];
    }

    #[DoNotSerialize]
    public function getDonor(): ?object
    {
        return $this->donor;
    }

    #[DoNotSerialize]
    public function getTrustCorporations(): array
    {
        return $this->trustCorporations ?? [];
    }

    #[DoNotSerialize]
    public function getStatus(): string
    {
        return $this->status ?? '';
    }
    #[DoNotSerialize]
    public function getUid(): string
    {
        return $this->uId ?? '';
    }
}
