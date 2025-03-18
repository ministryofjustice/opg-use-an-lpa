<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use DateTimeImmutable;

interface CodesApiValidationInterface
{
    public function getDob(): DateTimeImmutable;
}
