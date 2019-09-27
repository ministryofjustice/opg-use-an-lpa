<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ActorCodesFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['actor-codes-table'])) {
            throw new \Exception('Actor Codes table configuration not present');
        }

        return new ActorCodes(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['actor-codes-table']
        );
    }
}
