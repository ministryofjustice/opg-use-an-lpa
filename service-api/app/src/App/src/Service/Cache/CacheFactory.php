<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Psr16Cache;
use UnexpectedValueException;

class CacheFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function __invoke(string $cacheName): CacheInterface
    {
        $pool = $this->container->get('cache.' . $cacheName);

        if (!$pool instanceof CacheItemPoolInterface) {
            throw new UnexpectedValueException('cache.' . $cacheName . ' must implement CacheItemPoolInterface');
        }

        return new Psr16Cache($pool);
    }
}
