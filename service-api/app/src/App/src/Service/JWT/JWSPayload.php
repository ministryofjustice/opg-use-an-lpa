<?php

declare(strict_types=1);

namespace App\Service\JWT;

interface JWSPayload
{
    public function getPayload(): string;
}
