<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

interface SystemMessageService
{
    public function getSystemMessages(): array;
}
