<?php

declare(strict_types=1);

namespace Common\Service\Cache;

use Laminas\Cache\Storage\Adapter\Redis;
use Laminas\Cache\Storage\Adapter\RedisCluster;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;

use function assert;

final class RedisAdapterPluginManagerDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): AdapterPluginManager
    {
        $pluginManager = $callback();
        assert($pluginManager instanceof AdapterPluginManager);

        $pluginManager->configure(
            [
                'factories' => [
                    Redis::class => InvokableFactory::class,
                    RedisCluster::class => InvokableFactory::class,
                ],
                'aliases' => [
                    'redis' => Redis::class,
                    'Redis' => Redis::class,
                ],
            ]
        );

        return $pluginManager;
    }
}
