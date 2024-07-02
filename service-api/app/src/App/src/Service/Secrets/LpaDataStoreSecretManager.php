<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LpaDataStoreSecretManager implements SecretManagerInterface
{
    public const SECRET_NAME = 'lpa-data-store-secret';

    public function __construct(
        private SecretsManagerClient $secretsManagerClient,
        private LoggerInterface $logger,
    ) {
    }

    public function getSecret(): Secret
    {
        try {
            $response = $this->secretsManagerClient->getSecretValue([
                'SecretName' => self::SECRET_NAME,
            ])->get('SecretString');

            if ($response === null) {
                throw new RuntimeException('Key could not be found.');
            }

            return new Secret(new HiddenString($response));
        } catch (SecretsManagerException $e) {
            $this->logger->error('Could not fetch secrets from secrets manager: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAlgorithm(): string
    {
        return 'HS256';
    }
}
