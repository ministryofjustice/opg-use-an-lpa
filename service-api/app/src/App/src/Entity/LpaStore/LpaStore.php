<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\CastToCaseSubtype;
use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Lpa;
use App\Enum\ActorStatus;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\ResolveActor\ResolveActorInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use Exception;

class LpaStore extends Lpa
{
    public function __construct(
        #[CastListToType(LpaStoreAttorney::class)]
        array $attorneys,
        #[MapFrom('lpaType')]
        #[CastToCaseSubtype]
        LpaType $caseSubtype,
        string $channel,
        LpaStoreDonor $donor,
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions,
        #[MapFrom('lifeSustainingTreatmentOption')]
        #[CastToLifeSustainingTreatment]
        ?LifeSustainingTreatment $lifeSustainingTreatment,
        DateTimeImmutable $signedAt,
        DateTimeImmutable $registrationDate,
        ?string $restrictionsAndConditions,
        string $status,
        #[CastListToType(LpaStoreTrustCorporation::class)]
        ?array $trustCorporations,
        #[MapFrom('uid')]
        string $uId,
        DateTimeImmutable $updatedAt,
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
    ) {
        // Attorneys will still contain replacement and inactive variants. These will be filtered out
        // by /App/Service/Lpa/Combined/FilterActiveActors
        $replacementAttorneys = array_filter(
            $attorneys,
            function (GetAttorneyStatusInterface $attorney): bool {
                return $attorney->getStatus() === ActorStatus::REPLACEMENT;
            },
        );

        parent::__construct(
            applicationHasGuidance:     null,
            applicationHasRestrictions: null,
            applicationType:            null,
            attorneys:                  $attorneys,
            caseSubtype:                $caseSubtype,
            channel:                    $channel,
            dispatchDate:               null,
            donor:                      $donor,
            hasSeveranceWarning:        null,
            howAttorneysMakeDecisions:  $howAttorneysMakeDecisions,
            invalidDate:                null,
            lifeSustainingTreatment:    $lifeSustainingTreatment,
            lpaDonorSignatureDate:      $signedAt,
            lpaIsCleansed:              null,
            onlineLpaId:                null,
            receiptDate:                null,
            registrationDate:           $registrationDate,
            rejectedDate:               null,
            replacementAttorneys:       $replacementAttorneys,
            restrictionsAndConditions:  $restrictionsAndConditions,
            status:                     $status,
            statusDate:                 $updatedAt,
            trustCorporations:          $trustCorporations,
            uId:                        $uId,
            whenTheLpaCanBeUsed:        $whenTheLpaCanBeUsed,
            withdrawnDate:              null,
        );
    }

    public function getDonor(): ResolveActorInterface
    {
        if (!($this->donor instanceof ResolveActorInterface)) {
            throw new Exception(
                'Donor is not a valid ResolveActorInterface instance'
            );
        }

        return $this->donor;
    }

    public function getCaseSubType(): string
    {
        return $this->caseSubtype->value;
    }
}
