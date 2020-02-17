<?php

declare(strict_types=1);

namespace App\Service\Aws;

use Psr\Container\ContainerInterface;

/**
 * Builds a configured instance of the AWS DynamoDbClient.
 *
 * Class DynamoDbClientFactory
 * @package App\Service\Aws
 */
class DynamoDbClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(\Aws\Sdk::class)->createDynamoDb();
    }
}
