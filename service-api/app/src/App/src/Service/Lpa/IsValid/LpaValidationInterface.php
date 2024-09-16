<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

use App\DataAccess\ApiGateway\SiriusLpas;

interface LpaValidationInterface
{
    public function validate(array|object $lpa): bool;
}
