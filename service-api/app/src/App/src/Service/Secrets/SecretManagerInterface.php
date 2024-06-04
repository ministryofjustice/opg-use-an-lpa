<?php

declare(strict_types=1);

namespace App\Service\Secrets;

interface SecretManagerInterface
{
    public function getSecretName(): string;

    public function getSecret(): string;
}