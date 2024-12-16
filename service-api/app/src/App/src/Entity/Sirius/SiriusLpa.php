<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\FindActorInLpa\FindActorInLpaInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\ResolveActor\ResolveActorInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use Exception;

class SiriusLpa extends Lpa implements FindActorInLpaInterface
{
    public function __construct(
        ?bool $applicationHasGuidance,
        ?bool $applicationHasRestrictions,
        ?string $applicationType,
        #[CastListToType(SiriusLpaAttorney::class)]
        ?array $attorneys,
        ?bool $caseAttorneyJointly,
        ?bool $caseAttorneyJointlyAndJointlyAndSeverally,
        ?bool $caseAttorneyJointlyAndSeverally,
        ?LpaType $caseSubtype,
        ?string $channel,
        ?DateTimeImmutable $dispatchDate,
        ?SiriusLpaDonor $donor,
        ?bool $hasSeveranceWarning,
        ?DateTimeImmutable $invalidDate,
        #[CastToLifeSustainingTreatment]
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
        #[MapFrom('attorneyActDecisions')]
        #[CastToWhenTheLpaCanBeUsed]
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
        ?DateTimeImmutable $withdrawnDate,
    ) {
        $howAttorneysMakeDecisions = HowAttorneysMakeDecisions::fromDiscreteBooleans(
            jointly:                    $caseAttorneyJointly ?? false,
            jointlyAndSeverally:        $caseAttorneyJointlyAndSeverally ?? false,
            jointlyForSomeAndSeverally: $caseAttorneyJointlyAndJointlyAndSeverally ?? false,
        );

        parent::__construct(
            applicationHasGuidance:     $applicationHasGuidance,
            applicationHasRestrictions: $applicationHasRestrictions,
            applicationType:            $applicationType,
            attorneys:                  $attorneys,
            caseSubtype:                $caseSubtype,
            channel:                    $channel,
            dispatchDate:               $dispatchDate,
            donor:                      $donor,
            hasSeveranceWarning:        $hasSeveranceWarning,
            howAttorneysMakeDecisions:  $howAttorneysMakeDecisions,
            invalidDate:                $invalidDate,
            lifeSustainingTreatment:    $lifeSustainingTreatment,
            lpaDonorSignatureDate:      $lpaDonorSignatureDate,
            lpaIsCleansed:              $lpaIsCleansed,
            onlineLpaId:                $onlineLpaId,
            receiptDate:                $receiptDate,
            registrationDate:           $registrationDate,
            rejectedDate:               $rejectedDate,
            replacementAttorneys:       $replacementAttorneys,
            status:                     $status,
            statusDate:                 $statusDate,
            trustCorporations:          $trustCorporations,
            uId:                        $uId,
            whenTheLpaCanBeUsed:        $whenTheLpaCanBeUsed,
            withdrawnDate:              $withdrawnDate,
        );
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

    public function getCaseSubType(): string
    {
        return $this->caseSubtype->value;
    }
}
