<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\ResolveActor\CombinedHasActorTrait;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;

class Lpa implements
    JsonSerializable,
    HasActorInterface,
    IsValidInterface
{
    use CombinedHasActorTrait;

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

    public function getStatus(): string
    {
        return $this->status ?? '';
    }

    public function getUid(): string
    {
        return $this->uId ?? '';
    }

    private function getTrustCorporations(): array
    {
        return $this->trustCorporations ?? [];
    }
}
