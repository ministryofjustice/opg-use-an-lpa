<?php

declare(strict_types=1);

namespace App\Command;

use App\DataAccess\ApiGateway\Lpas;
use Aws\DynamoDb\DynamoDbClient;
use DI\Container;
use Psr\Container\ContainerInterface;

class ActorCodeCreationCommandFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['actor-codes-table'])) {
            throw new \Exception('Actor Codes table configuration not present');
        }

        // The trace-id is not available to services requesting it under CLI running. Lpas::class is one
        // of those so here we add a dummy value. Runtime modification of the container outside
        // of testing contexts should not generally be done.
        if ($container instanceof Container) {
            $container->set('trace-id', 'CLI_TRACE_ID');
        }

        return new ActorCodeCreationCommand(
            $container->get(Lpas::class),
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['actor-codes-table']
        );
    }
}
