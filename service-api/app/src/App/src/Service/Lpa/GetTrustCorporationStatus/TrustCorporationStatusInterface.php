<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

interface TrustCorporationStatusInterface
{
    public function getCompanyName(): string;

    public function getSystemStatus(): bool;

    public function getUid(): string;
}