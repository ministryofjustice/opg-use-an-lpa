<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

class OneLoginUserInfoKeyPairManager extends AbstractKeyPairManager
{
    public const PUBLIC_KEY = 'gov_uk_onelogin_userinfo_public_key';

    public function getKeyPair(): KeyPair
    {
        return $this->fetchKeyPairFromSecretsManager(self::PUBLIC_KEY);
    }

    public function getAlgorithm(): string
    {
        return 'ES256';
    }
}
