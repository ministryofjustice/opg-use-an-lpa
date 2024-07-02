<?php

declare(strict_types=1);

namespace App\Service\Secrets;

interface KeyPairManagerInterface
{
    public const PUBLIC_KEY = '';

    public function getKeyPair(): KeyPair;

    public function getAlgorithm(): string;
}
