<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Redis;

/**
 * @psalm-api
 */
final class RedisFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Redis
    {
        return new Redis();
    }
}
