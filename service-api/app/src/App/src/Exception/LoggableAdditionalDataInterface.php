<?php

declare(strict_types=1);

namespace App\Exception;

interface LoggableAdditionalDataInterface
{
    public function getAdditionalDataForLogging(): array;
}
