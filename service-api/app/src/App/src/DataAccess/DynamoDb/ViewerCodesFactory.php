<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ViewerCodesFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['viewer-codes-table'])) {
            throw new \Exception('Viewer Codes table configuration not present');
        }

        return new ViewerCodes(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['viewer-codes-table']
        );
    }
}
