<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;
use App\Handler\HealthcheckHandler;
use App\DataAccess\Repository\LpasInterface;

class HealthcheckHandlerFactory
{
    public function __invoke(ContainerInterface $container) : HealthcheckHandler
    {
        return new HealthcheckHandler(
            $container->get('config')['version'],
            $container->get(DynamoDbClient::class),
            $container->get(LpasInterface::class));
    }
}