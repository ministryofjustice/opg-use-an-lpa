<?php

declare(strict_types=1);

namespace App\Service\Lpa\FindActorInLpa;

use DateTimeInterface;

interface ActorMatchingInterface
{
    public function getDob(): DateTimeInterface;

    public function getFirstname(): string;

    public function getPostCode(): string;

    public function getSurname(): string;

    public function getUid(): string;
}
