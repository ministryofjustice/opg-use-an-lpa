<?php

declare(strict_types=1);

namespace Viewer\Handler\Factory;

use Psr\Container\ContainerInterface;
use Viewer\Handler\HealthcheckHandler;
use Viewer\Service\ApiClient\Client as ApiClient;

class HealthcheckHandlerFactory
{
    public function __invoke(ContainerInterface $container) : HealthcheckHandler
    {
        return new HealthcheckHandler($container->get('config')['version'], $container->get(ApiClient::class));
    }
}