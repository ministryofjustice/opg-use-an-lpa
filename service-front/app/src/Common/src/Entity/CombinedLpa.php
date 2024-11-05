<?php

declare(strict_types=1);

namespace Common\Entity;

use Behat\Step\When;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use JsonSerializable;

class CombinedLpa implements JsonSerializable
{
    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        public readonly ?HowAttorneysMakeDecisions $attorneyActDecisions,
        /** @var Person[] $attorneys */
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
        /** @var Person[] $replacementAttorneys */
        public readonly ?array $replacementAttorneys,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $statusDate,
        /** @var SiriusLpaTrustCorporations[] $trustCorporations */
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        public readonly ?DateTimeImmutable $withdrawnDate,
        public readonly ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
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
    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[DoNotSerialize]
    public function getLifeSustainingTreatment(): string
    {
        return $this->lifeSustainingTreatment->value;
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
    public function getCaseSubtype(): ?LpaType
    {
        return $this->caseSubtype;
    }

    #[DoNotSerialize]
    public function getCaseAttorneySingular(): bool
    {
        return $this->attorneyActDecisions === HowAttorneysMakeDecisions::SINGULAR;
    }

    #[DoNotSerialize]
    public function getCaseAttorneyJointly(): bool
    {
        return $this->attorneyActDecisions === HowAttorneysMakeDecisions::JOINTLY;
    }

    #[DoNotSerialize]
    public function getCaseAttorneyJointlyAndSeverally(): bool
    {
        return $this->attorneyActDecisions === HowAttorneysMakeDecisions::JOINTLY_AND_SEVERALLY;
    }

    #[DoNotSerialize]
    public function caseAttorneyJointlyAndJointlyAndSeverally(): bool
    {
        return $this->attorneyActDecisions === HowAttorneysMakeDecisions::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS;
    }

    #[DoNotSerialize]
    public function getActiveAttorneys(): ?array
    {
        return $this->attorneys;
    }

    #[DoNotSerialize]
    public function getTrustCorporations(): ?array
    {
        return $this->trustCorporations;
    }

    #[DoNotSerialize]
    public function getWhenTheLpaCanBeUsed(): WhenTheLpaCanBeUsed
    {
        return $this->whenTheLpaCanBeUsed;
    }
}
