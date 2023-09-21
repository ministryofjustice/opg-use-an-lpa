<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\Cache\Storage\Adapter\Apcu;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class CacheFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function __invoke(string $cacheName): CacheInterface
    {
        $config = $this->container->get('config');

        if (!isset($config['cache'])) {
            throw new RuntimeException('Missing cache configuration');
        }
        if (!isset($config['cache'][$cacheName])) {
            throw new RuntimeException('Missing cache configuration for ' . $cacheName);

        }
        $factory = $this->container->get(StorageAdapterFactoryInterface::class);
        /** @var Apcu $cacheAdaptor */
        $cacheAdaptor = $factory->createFromArrayConfiguration($config['cache'][$cacheName]);
        return new SimpleCacheDecorator($cacheAdaptor);
    }
}
