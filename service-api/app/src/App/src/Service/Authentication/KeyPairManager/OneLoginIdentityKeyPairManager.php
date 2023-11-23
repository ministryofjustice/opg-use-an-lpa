<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OneLoginIdentityKeyPairManager extends AbstractKeyPairManager implements KeyPairManagerInterface
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
