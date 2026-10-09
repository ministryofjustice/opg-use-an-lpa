<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\LpasInterface;
use App\Service\Cache\CacheFactory;
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
        $cacheFactory = $container->get(CacheFactory::class);

        return new CachedSiriusLpas(
            /** @var SiriusLpas */
            call_user_func($callback),
            ($cacheFactory)('request'),
            $container->get(LoggerInterface::class),
        );
    }
}
