<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class AbstractKeyPairManager implements KeyPairManagerInterface
{
    public function __construct(private SecretsManagerClient $secretsManagerClient, private LoggerInterface $logger)
    {
    }

    protected function fetchKeyPairFromSecretsManager(string $publicKeyName, ?string $privateKeyName = null): KeyPair
    {
        try {
            $public = $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => $publicKeyName,
                ],
            )->get('SecretString');

            $private = $privateKeyName ? $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => $privateKeyName,
                ],
            )->get('SecretString') : null;
        } catch (SecretsManagerException $e) {
            $this->logger->error('Could not fetch secrets from secrets manager: ' . $e->getMessage());
            throw $e;
        }

        if ($public === null || ($privateKeyName !== null && $private === null)) {
            throw new RuntimeException('Key could not be found. Could not create KeyPair');
        }

        $private = $privateKeyName ? new HiddenString($private, true, false) : null;

        return new KeyPair($public, $private);
    }

    abstract public function getKeyPair(): KeyPair;

    abstract public function getAlgorithm(): string;
}
