<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class AbstractSecretManager implements SecretManagerInterface
{
    public function __construct(
        private SecretsManagerClient $secretsManagerClient,
        private LoggerInterface $logger,
    ) {
    }

    abstract public function getSecretName(): string;

    /**
     * @throws SecretsManagerException
     * @throws RuntimeException
     */
    public function getSecret(): string
    {
        try {
            $response = $this->secretsManagerClient->getSecretValue([
              'SecretName' => $this->getSecretName(),
            ])->get('SecretString');

            if ($response === null) {
                throw new RuntimeException('Key could not be found.');
            }

            return $response;
        } catch (SecretsManagerException $e) {
            $this->logger->error('Could not fetch secrets from secrets manager: ' . $e->getMessage());
            throw $e;
        }
    }
}