<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use Psr\Container\ContainerInterface;
use App\Handler\HealthcheckHandler;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\ActorCodesInterface;

class HealthcheckHandlerFactory
{
    public function __invoke(ContainerInterface $container) : HealthcheckHandler
    {
        return new HealthcheckHandler(
            $container->get('config')['version'],
            $container->get(LpasInterface::class),
            $container->get(ActorCodesInterface::class));
    }
}