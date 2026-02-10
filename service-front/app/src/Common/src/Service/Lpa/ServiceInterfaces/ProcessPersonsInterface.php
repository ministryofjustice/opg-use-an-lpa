<?php

declare(strict_types=1);

namespace Common\Service\Lpa\ServiceInterfaces;

use DateTimeInterface;

interface ProcessPersonsInterface
{
    public function getDob(): DateTimeInterface;

    public function getFirstname(): string;

    public function getMiddlenames(): string;

    public function getSurname(): string;

    public function getSystemStatus(): bool;

    public function getCannotMakeJointDecisions(): ?bool;
}
