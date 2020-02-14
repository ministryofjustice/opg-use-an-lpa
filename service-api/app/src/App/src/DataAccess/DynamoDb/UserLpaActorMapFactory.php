<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class UserLpaActorMapFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['user-lpa-actor-map'])) {
            throw new \Exception('UserLpaActorMap table configuration not present');
        }

        return new UserLpaActorMap(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['user-lpa-actor-map']
        );
    }
}
