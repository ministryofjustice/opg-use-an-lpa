<?php

declare(strict_types=1);

namespace Common\Service\Aws;

use Aws\Sdk;
use Psr\Container\ContainerInterface;

/**
 * Builds a configured instance of the AWS KMS Client
 */
class KmsFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(Sdk::class)->createKms();
    }
}
