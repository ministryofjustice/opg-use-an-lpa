<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ActorUsersFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if ( !isset($config['repositories']['dynamodb']['actor-users-table'])) {
            throw new \Exception('Actor Users table configuration not present');
        }

        return new ActorUsers(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['actor-users-table']
        );
    }
}
