<?php
declare(strict_types=1);

namespace App\Service\Aws;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;
use RuntimeException;

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
        $config = $container->get('config');

        if (!isset($config['aws'])) {
            throw new RuntimeException('Missing aws configuration');
        }

        return new DynamoDbClient($config['aws']['dynamodb']);
    }
}
