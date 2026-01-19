<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

use App\Enum\ActorStatus;

interface GetTrustCorporationStatusInterface
{
    public function getCompanyName(): ?string;

    public function getStatus(): bool|ActorStatus;

    public function getUid(): string;

    public function getCannotMakeJointDecisions(): ?bool;
}
