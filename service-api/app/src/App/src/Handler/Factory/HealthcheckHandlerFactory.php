<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;
use App\Handler\HealthcheckHandler;
use App\Service\ApiClient\Client as ApiClient;

class HealthcheckHandlerFactory
{
    public function __invoke(ContainerInterface $container) : HealthcheckHandler
    {
        return new HealthcheckHandler($container->get('config')['version'], $container->get(ApiClient::class), $container->get(DynamoDbClient::class));
    }
}