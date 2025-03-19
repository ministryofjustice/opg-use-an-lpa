<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use DateTimeInterface;

interface CodesApiValidationInterface
{
    public function getDob(): DateTimeInterface;
}
