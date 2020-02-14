<?php

declare(strict_types=1);

namespace Common\Service\Aws;

use Psr\Container\ContainerInterface;

/**
 * Builds a configured instance of the AWS Secrets Manager Client
 *
 * Class SecretsManagerFactory
 * @package Common\Service\Aws
 */
class SecretsManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(\Aws\Sdk::class)->createSecretsManager();
    }
}
