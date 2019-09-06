<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ActorLpaCodesFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['actor-lpa-codes-table'])) {
            throw new \Exception('Actor LPA Codes table configuration not present');
        }

        return new ActorLpaCodes(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['actor-lpa-codes-table']
        );
    }
}
