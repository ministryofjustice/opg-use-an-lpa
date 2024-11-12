<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface GetAttorneyStatusInterface
{
    public function getFirstnames(): string;

    public function getSurname(): string;

    public function getStatus(): bool|string;
}
