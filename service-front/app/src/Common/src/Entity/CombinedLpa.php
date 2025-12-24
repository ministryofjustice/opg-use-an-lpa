<?php

declare(strict_types=1);

namespace Common\Entity;

use Common\Entity\Casters\CastToCaseSubtype;
use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use Common\Entity\Casters\CastToLifeSustainingTreatment;
use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use Common\Enum\Channel;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Service\Lpa\ServiceInterfaces\GroupLpasInterface;
use Common\Service\Lpa\ServiceInterfaces\ProcessLpasInterface;
use Common\Service\Lpa\ServiceInterfaces\SortLpasInterface;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

class CombinedLpa implements SortLpasInterface, GroupLpasInterface, ProcessLpasInterface
{
    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        /** @var Person[] $attorneys */
        #[CastListToType(Person::class)]
        public readonly ?array $attorneys,
        #[CastToCaseSubtype]
        public readonly ?LpaType $caseSubtype,
        public readonly ?string $channel,
        public readonly ?DateTimeImmutable $dispatchDate,
        public readonly ?Person $donor,
        public readonly ?bool $hasSeveranceWarning,
        #[CastToHowAttorneysMakeDecisions]
        public readonly ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions,
        public readonly ?string $howAttorneysMakeDecisionsDetails,
        public readonly ?DateTimeImmutable $invalidDate,
        #[CastToLifeSustainingTreatment]
        public readonly ?LifeSustainingTreatment $lifeSustainingTreatment,
        public readonly ?DateTimeImmutable $lpaDonorSignatureDate,
        public readonly ?bool $lpaIsCleansed,
        public readonly ?string $onlineLpaId,
        public readonly ?DateTimeImmutable $receiptDate,
        public readonly ?DateTimeImmutable $registrationDate,
        public readonly ?DateTimeImmutable $rejectedDate,
        /** @var Person[] $replacementAttorneys */
        #[CastListToType(Person::class)]
        public readonly ?array $replacementAttorneys,
        public readonly ?string $restrictionsAndConditions,
        public readonly ?array $restrictionsAndConditionsImages,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $statusDate,
        /** @var Person[] $trustCorporations */
        #[CastListToType(Person::class)]
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        #[CastToWhenTheLpaCanBeUsed]
        public readonly ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
        public readonly ?DateTimeImmutable $withdrawnDate,
    ) {
    }

    public function getLpaDonorSignatureDate(): ?DateTimeImmutable
    {
        return $this->lpaDonorSignatureDate;
    }

    public function getUId(): ?string
    {
        return $this->uId;
    }

    public function getApplicationHasGuidance(): ?bool
    {
        return $this->applicationHasGuidance;
    }

    public function getApplicationHasRestrictions(): ?bool
    {
        return $this->applicationHasRestrictions;
    }

    public function getAttorneys(): ?array
    {
        return $this->attorneys;
    }

    public function getDonor(): Person
    {
        return $this->donor;
    }

    public function getCaseSubtype(): string
    {
        return $this->caseSubtype->value;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getLifeSustainingTreatment(): string
    {
        return $this->lifeSustainingTreatment->value;
    }

    public function getHowAttorneysMakeDecisions(): HowAttorneysMakeDecisions
    {
        return $this->howAttorneysMakeDecisions;
    }

    public function getCaseAttorneySingular(): bool
    {
        return $this->howAttorneysMakeDecisions === HowAttorneysMakeDecisions::SINGULAR;
    }

    public function getCaseAttorneyJointly(): bool
    {
        return $this->howAttorneysMakeDecisions === HowAttorneysMakeDecisions::JOINTLY;
    }

    public function getCaseAttorneyJointlyAndSeverally(): bool
    {
        return $this->howAttorneysMakeDecisions === HowAttorneysMakeDecisions::JOINTLY_AND_SEVERALLY;
    }

    public function getCaseAttorneyJointlyAndJointlyAndSeverally(): bool
    {
        return $this->howAttorneysMakeDecisions === HowAttorneysMakeDecisions::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS;
    }

    public function getActiveAttorneys(): ?array
    {
        return $this->attorneys;
    }

    public function getTrustCorporations(): ?array
    {
        return $this->trustCorporations;
    }

    public function getWhenTheLpaCanBeUsed(): WhenTheLpaCanBeUsed
    {
        return $this->whenTheLpaCanBeUsed;
    }

    public function getLpaType(): LpaType
    {
        return LpaType::from($this->getCaseSubtype());
    }

    public function getChannel(): Channel
    {
        return Channel::from($this->channel);
    }
}
