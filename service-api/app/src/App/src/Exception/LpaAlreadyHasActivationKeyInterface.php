<?php

declare(strict_types=1);

namespace App\Exception;

interface LpaAlreadyHasActivationKeyInterface
{
    public function getUid(): string;
}
