<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\Casters\CastToCaseSubtype;
use App\Entity\Casters\CastToHowAttorneysMakeDecisions;
use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Lpa;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use JsonSerializable;

class LpaStore extends Lpa implements JsonSerializable
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
        $whenTheLpaCanBeUsed
    ) {
        parent::__construct(
            applicationHasGuidance: $applicationHasGuidance,
            applicationHasRestrictions: $applicationHasRestrictions,
            applicationType: $applicationType,
            attorneys: $attorneys,
            caseSubtype: $caseSubtype,
            channel: $channel,
            dispatchDate: $dispatchDate,
            donor: $donor,
            hasSeveranceWarning: $hasSeveranceWarning,
            howAttorneysMakeDecisions: $howAttorneysMakeDecisions,
            invalidDate: $invalidDate,
            lifeSustainingTreatment: $lifeSustainingTreatment,
            lpaDonorSignatureDate: $lpaDonorSignatureDate,
            lpaIsCleansed: $lpaIsCleansed,
            onlineLpaId: $onlineLpaId,
            receiptDate: $receiptDate,
            registrationDate: $registrationDate,
            rejectedDate: $rejectedDate,
            replacementAttorneys: $replacementAttorneys,
            status: $status,
            statusDate: $statusDate,
            trustCorporations: $trustCorporations,
            uId: $uId,
            withdrawnDate: $withdrawnDate,
            whenTheLpaCanBeUsed: $whenTheLpaCanBeUsed
        );
    }

    #[DoNotSerialize]
    public function jsonSerialize(): mixed
    {
        $data = get_object_vars($this);

        array_walk($data, function (&$value) {
            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s.uO');
            }
        });

        return $data;
    }
}
