<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

interface TrustCorporationStatusInterface
{
    public function getCompanyName(): string|null;

    public function getSystemStatus(): bool|string;

    public function getUid(): string;
}
