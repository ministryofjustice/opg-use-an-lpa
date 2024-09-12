<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

interface LpaValidationInterface
{
    public function validate(object $lpa): bool;
}
