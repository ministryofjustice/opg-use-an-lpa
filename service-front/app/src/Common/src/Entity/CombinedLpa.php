<?php

declare(strict_types=1);

namespace Common\Entity;

use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Service\Lpa\ServiceInterfaces\GroupLpasInterface;
use Common\Service\Lpa\ServiceInterfaces\SortLpasInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use JsonSerializable;

class CombinedLpa implements JsonSerializable, SortLpasInterface, GroupLpasInterface
{
    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        public readonly ?HowAttorneysMakeDecisions $attorneyActDecisions,
        public readonly ?array $attorneys,
        public readonly ?LpaType $caseSubtype,
        public readonly ?string $channel,
        public readonly ?DateTimeImmutable $dispatchDate,
        public readonly ?Person $donor,
        public readonly ?bool $hasSeveranceWarning,
        public readonly ?DateTimeImmutable $invalidDate,
        public readonly ?LifeSustainingTreatment $lifeSustainingTreatment,
        public readonly ?DateTimeImmutable $lpaDonorSignatureDate,
        public readonly ?bool $lpaIsCleansed,
        public readonly ?string $onlineLpaId,
        public readonly ?DateTimeImmutable $receiptDate,
        public readonly ?DateTimeImmutable $registrationDate,
        public readonly ?DateTimeImmutable $rejectedDate,
        public readonly ?array $replacementAttorneys,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $statusDate,
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        public readonly ?DateTimeImmutable $withdrawnDate,
    ) {
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

    #[DoNotSerialize]
    public function getLpaDonorSignatureDate(): ?DateTimeImmutable
    {
        return $this->lpaDonorSignatureDate;
    }

    #[DoNotSerialize]
    public function getUId(): ?string
    {
        return $this->uId;
    }

    #[DoNotSerialize]
    public function getApplicationHasGuidance(): ?bool
    {
        return $this->applicationHasGuidance;
    }

    #[DoNotSerialize]
    public function getApplicationHasRestrictions(): ?bool
    {
        return $this->applicationHasRestrictions;
    }

    #[DoNotSerialize]
    public function getDonor(): Person
    {
        return $this->donor;
    }

    #[DoNotSerialize]
    public function getCaseSubtype(): string
    {
        return $this->caseSubtype->value;
    }
}
