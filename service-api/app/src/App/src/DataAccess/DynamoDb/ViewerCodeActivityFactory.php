<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class ViewerCodeActivityFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['repositories']['dynamodb']['viewer-activity-table'])) {
            throw new \Exception('Viewer Activity table configuration not present');
        }

        return new ViewerCodeActivity(
            $container->get(DynamoDbClient::class),
            $config['repositories']['dynamodb']['viewer-activity-table']
        );
    }
}
