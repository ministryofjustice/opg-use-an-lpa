<?php

declare(strict_types=1);

namespace Common\Handler\Factory;

use Common\Service\ApiClient\Client as ApiClient;
use Psr\Container\ContainerInterface;
use Common\Handler\HealthcheckHandler;

class HealthcheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): HealthcheckHandler
    {
        return new HealthcheckHandler($container->get('config')['version'], $container->get(ApiClient::class));
    }
}
