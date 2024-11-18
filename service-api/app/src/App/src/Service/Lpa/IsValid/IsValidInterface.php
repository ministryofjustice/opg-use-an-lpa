<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

interface IsValidInterface
{
    public function getStatus(): string;

    public function getUid(): string;
}
