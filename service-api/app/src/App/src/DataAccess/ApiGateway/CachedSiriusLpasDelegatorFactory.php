<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\LpasInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CachedSiriusLpasDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): LpasInterface {
        return new CachedSiriusLpas(
            /** @var SiriusLpas */
            call_user_func($callback),
            $container->get(LoggerInterface::class),
        );
    }
}
