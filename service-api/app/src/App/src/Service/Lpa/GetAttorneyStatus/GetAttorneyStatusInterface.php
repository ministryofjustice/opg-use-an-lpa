<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetAttorneyStatus;

use App\Enum\ActorStatus;

interface GetAttorneyStatusInterface
{
    public function getFirstnames(): string;

    public function getSurname(): string;

    public function getStatus(): bool|ActorStatus;

    public function getUid(): string;

    public function getCannotMakeJointDecisions(): ?bool;
}
