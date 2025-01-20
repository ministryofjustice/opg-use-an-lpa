<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

interface GetAttorneyStatusInterface
{
    public function getFirstnames(): string;

    public function getSurname(): string;

    public function getStatus(): bool|string;
}
