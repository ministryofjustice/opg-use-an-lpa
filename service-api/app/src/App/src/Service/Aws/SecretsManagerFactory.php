<?php

declare(strict_types=1);

namespace App\Service\Aws;

use Aws\Sdk;
use Psr\Container\ContainerInterface;

/**
 * Builds a configured instance of the AWS Secrets Manager Client
 */
class SecretsManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(Sdk::class)->createSecretsManager();
    }
}
