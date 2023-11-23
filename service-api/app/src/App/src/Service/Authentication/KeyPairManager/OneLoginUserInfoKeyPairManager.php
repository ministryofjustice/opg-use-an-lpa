<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OneLoginUserInfoKeyPairManager extends AbstractKeyPairManager implements KeyPairManagerInterface
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
