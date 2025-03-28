<?php

declare(strict_types=1);

namespace App\Service\Lpa\RestrictSendingLpaForCleansing;

use DateTimeInterface;

interface RestrictSendingLpaForCleansingInterface
{
    public function getRegistrationDate(): DateTimeInterface;

    public function getLpaIsCleansed(): bool;

    public function getUid(): string;
}
