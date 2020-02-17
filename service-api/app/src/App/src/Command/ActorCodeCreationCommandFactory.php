<?php

declare(strict_types=1);

namespace App\Command;

use App\DataAccess\ApiGateway\Lpas;
use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ActorCodeCreationCommandFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['actor-codes-table'])) {
            throw new \Exception('Actor Codes table configuration not present');
        }

        return new ActorCodeCreationCommand(
            $container->get(Lpas::class),
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['actor-codes-table']
        );
    }
}
