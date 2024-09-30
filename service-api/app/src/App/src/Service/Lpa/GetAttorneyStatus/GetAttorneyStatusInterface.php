<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

interface GetAttorneyStatusInterface
{
    public function getFirstname(): string;

    public function getSurname(): string;

    public function getSystemStatus(): bool;
}
