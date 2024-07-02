<?php

declare(strict_types=1);

namespace App\Service\Secrets;

interface SecretManagerInterface
{
    public function getSecret(): Secret;

    public function getAlgorithm(): string;
}
