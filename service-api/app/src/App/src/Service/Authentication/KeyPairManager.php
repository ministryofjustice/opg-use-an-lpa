<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class KeyPairManager
{
    public const PUBLIC_KEY  = 'gov_uk_onelogin_identity_public_key';
    public const PRIVATE_KEY = 'gov_uk_onelogin_identity_private_key';

    public function __construct(private SecretsManagerClient $secretsManagerClient, private LoggerInterface $logger)
    {
    }

    public function getKeyPair(): KeyPair
    {
        try {
            $public  = $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => self::PUBLIC_KEY,
                ]
            )->get('SecretString');
            $private = $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => self::PRIVATE_KEY,
                ]
            )->get('SecretString');
        } catch (SecretsManagerException $e) {
            $this->logger->error('Could not fetch secrets from secrets manager: ' . $e->getMessage());
            throw $e;
        }

        if ($private === null || $public === null) {
            throw new RuntimeException('Key could not be found. Could not create KeyPair');
        }

        $private = new HiddenString($private, true, false);
        return new KeyPair($public, $private);
    }
}
