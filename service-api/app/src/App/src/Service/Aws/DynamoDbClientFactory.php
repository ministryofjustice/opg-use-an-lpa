<?php

declare(strict_types=1);

namespace App\Service\Aws;

use Psr\Container\ContainerInterface;
use Aws\Sdk;

/**
 * Builds a configured instance of the AWS DynamoDbClient.
 */
class DynamoDbClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(Sdk::class)->createDynamoDb();
    }
}
