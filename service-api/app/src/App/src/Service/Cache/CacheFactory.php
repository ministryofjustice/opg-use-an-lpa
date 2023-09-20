<?php

namespace Api\Service\Cache;

use Psr\Container\ContainerInterface;
use Laminas\Cache\Storage\Adapter\Apcu;

class CacheFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $cache = new Apcu();

        $cache->setOptions([
          'ttl' => 3600,
        ]);

        return $cache;
    }
}
