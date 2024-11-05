<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface GetTrustCorporationStatusInterface
{
    public function getCompanyName(): ?string;

    public function getStatus(): bool|string;

    public function getUid(): string;
}
