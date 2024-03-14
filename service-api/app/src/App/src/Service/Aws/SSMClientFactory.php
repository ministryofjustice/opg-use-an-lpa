<?php

declare(strict_types=1);

namespace App\Service\Aws;

use Aws\Sdk;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Builds a configured instance of the AWS Secrets Manager Client
 */
class SSMClientFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(Sdk::class)->createSsm();
    }
}
