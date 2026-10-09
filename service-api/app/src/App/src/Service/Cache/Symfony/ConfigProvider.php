<?php

declare(strict_types=1);

namespace App\Service\Cache\Symfony;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @psalm-api
 */
final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'  => [
                'aliases'   => [
                    CacheItemPoolInterface::class => 'cache.request',
                    CacheInterface::class         => 'cache.request',
                    TagAwareCacheInterface::class => 'cache.request.taggable',
                ],
                'factories' => [
                    'cache.app'              => PoolFactory::class,
                    'cache.app.taggable'     => TagAwareFactory::class,
                    'cache.request'          => PoolFactory::class,
                    'cache.request.taggable' => TagAwareFactory::class,
                ],
            ],
            'symfony_cache' => [
                'namespace' => 'opg-use-an-lpa',
                'version'   => '1',
                'pools'     => [
                    'cache.request' => [
                        'adapter'          => 'array',
                        'default_lifetime' => 0,
                    ],
                    'cache.app'     => [
                        'adapter'          => 'apcu',
                        'default_lifetime' => 0,
                    ],
                ],
            ],
        ];
    }
}
