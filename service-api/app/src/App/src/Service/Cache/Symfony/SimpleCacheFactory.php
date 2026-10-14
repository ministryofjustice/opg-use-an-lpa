<?php

declare(strict_types=1);

namespace App\Service\Cache\Symfony;

use App\Service\Cache\CacheFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

final class SimpleCacheFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): CacheInterface {
        return (new CacheFactory($container))('request');
    }
}
