<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use App\Exception\ApiException;
use App\Service\Lpa\AddLpa\AddLpaInterface;
use App\Service\Lpa\Combined\FilterActiveActorsInterface;
use App\Service\Lpa\HasRestrictionsInterface;
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\LpaAlreadyAdded\DonorInformationInterface;
use App\Service\Lpa\LpaAlreadyAdded\LpaAlreadyAddedInterface;
use App\Service\Lpa\ResolveActor\CombinedHasActorTrait;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\RestrictSendingLpaForCleansing\RestrictSendingLpaForCleansingInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Spatie\Cloneable\Cloneable;

class Lpa implements
    JsonSerializable,
    HasActorInterface,
    IsValidInterface,
    AddLpaInterface,
    LpaAlreadyAddedInterface,
    HasRestrictionsInterface,
    FilterActiveActorsInterface,
    RestrictSendingLpaForCleansingInterface
{
    use Cloneable;
    use CombinedHasActorTrait;

    public function __construct(
        public readonly ?bool $applicationHasGuidance,
        public readonly ?bool $applicationHasRestrictions,
        public readonly ?string $applicationType,
        /** @var Person[] $attorneys */
        public readonly ?array $attorneys,
        public readonly ?LpaType $caseSubtype,
        public readonly ?string $channel,
        public readonly ?DateTimeImmutable $dispatchDate,
        public readonly ?Person $donor,
        public readonly ?bool $hasSeveranceWarning,
        public readonly ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions,
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
        public readonly ?string $restrictionsAndConditions,
        public readonly ?array $restrictionsAndConditionsImages,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $statusDate,
        /** @var Person[] $trustCorporations */
        public readonly ?array $trustCorporations,
        public readonly ?string $uId,
        public readonly ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed,
        public readonly ?DateTimeImmutable $withdrawnDate,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);

        array_walk($data, function (&$value) {
            if ($value instanceof DateTimeImmutable) {
                $value = $value->setTimezone(new DateTimeZone('UTC'));
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }
        });

        return $data;
    }

    public function getAttorneys(): array
    {
        return $this->attorneys ?? [];
    }

    public function withAttorneys(array $attorneys): self
    {
        return $this->with(attorneys: $attorneys);
    }

    public function getStatus(): string
    {
        return $this->status ?? '';
    }

    public function getUid(): string
    {
        return $this->uId ?? '';
    }

    public function getTrustCorporations(): array
    {
        return $this->trustCorporations ?? [];
    }

    public function withTrustCorporations(array $trustCorporations): self
    {
        return $this->with(trustCorporations: $trustCorporations);
    }

    public function getRegistrationDate(): DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function getLpaIsCleansed(): bool
    {
        return $this->lpaIsCleansed ?? false;
    }

    public function hasGuidance(): bool
    {
        return $this->applicationHasGuidance ?? false;
    }

    public function hasRestrictions(): bool
    {
        return $this->applicationHasRestrictions ?? false;
    }

    public function getDonor(): Person
    {
        if (!isset($this->donor)) {
            throw ApiException::create('Donor does not exist and is required');
        }
        return $this->donor;
    }

    public function getCaseSubType(): string
    {
        if (!isset($this->caseSubtype)) {
            return '';
        }
        return $this->caseSubtype->value;
    }
}
