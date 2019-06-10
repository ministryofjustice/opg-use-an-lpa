<?php
declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository;
use Aws;
use Psr\Container\ContainerInterface;

class LpaServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if ( !isset($config['repositories']['dynamodb']['viewer-codes-table'])) {
            throw new \Exception('Viewer Codes table configuration not present');
        }

        return new LpaService(
            $container->get(Aws\DynamoDb\DynamoDbClient::class),
            $config['repositories']['dynamodb']['viewer-codes-table'],
            $container->get(Repository\ViewerCodeActivityInterface::class),
        );
    }
}
