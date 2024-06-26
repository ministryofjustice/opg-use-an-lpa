<?php

declare(strict_types=1);

namespace App\Service\Secrets;

class OneLoginIdentityKeyPairManager extends AbstractKeyPairManager
{
    public const PUBLIC_KEY  = 'gov-uk-onelogin-identity-public-key';
    public const PRIVATE_KEY = 'gov-uk-onelogin-identity-private-key';

    public function getKeyPair(): KeyPair
    {
        return $this->fetchKeyPairFromSecretsManager(self::PUBLIC_KEY, self::PRIVATE_KEY);
    }

    public function getAlgorithm(): string
    {
        return 'RS256';
    }
}
