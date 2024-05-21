<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

class LpaDataStoreKeyPairManager extends AbstractKeyPairManager
{
    public const PUBLIC_KEY  = 'lpa-data-store-public-key';
    public const PRIVATE_KEY = 'lpa-data-store-private-key';

    public function getKeyPair(): KeyPair
    {
        return $this->fetchKeyPairFromSecretsManager(self::PUBLIC_KEY, self::PRIVATE_KEY);
    }

    public function getAlgorithm(): string
    {
        return 'RS256';
    }
}
