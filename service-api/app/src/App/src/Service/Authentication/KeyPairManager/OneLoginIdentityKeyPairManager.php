<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

class OneLoginIdentityKeyPairManager extends AbstractKeyPairManager
{
    public const PUBLIC_KEY  = 'gov_uk_onelogin_identity_public_key';
    public const PRIVATE_KEY = 'gov_uk_onelogin_identity_private_key';

    public function getKeyPair(): KeyPair
    {
        return $this->fetchKeyPairFromSecretsManager(self::PUBLIC_KEY, self::PRIVATE_KEY);
    }

    public function getAlgorithm(): string
    {
        return 'RS256';
    }
}
