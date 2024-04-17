<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheException;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class CacheFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $cacheName
     * @return CacheInterface
     * @throws RuntimeException
     * @throws SimpleCacheException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(string $cacheName): CacheInterface
    {
        $config = $this->container->get('config');

        if (!isset($config['cache'])) {
            throw new RuntimeException('Missing cache configuration');
        }
        if (!isset($config['cache'][$cacheName])) {
            throw new RuntimeException('Missing cache configuration for ' . $cacheName);
        }

        /** @var StorageAdapterFactoryInterface $factory */
        $factory = $this->container->get(StorageAdapterFactoryInterface::class);

        $cacheAdaptor = $factory->createFromArrayConfiguration($config['cache'][$cacheName]);

        return new SimpleCacheDecorator($cacheAdaptor);
    }
}
