<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use DateTimeImmutable;

interface CodesApiValidationStrategyInterface
{
    public function getDob(): DateTimeImmutable;
}
