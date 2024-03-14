<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

use App\Service\Cache\CacheFactory;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;

class CachedKeyPairManagerDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): KeyPairManagerInterface {
        $cacheFactory = $container->get(CacheFactory::class);

        return new CachedKeyPairManager(
            ($cacheFactory)('one-login'),
            /** @var AbstractKeyPairManager */
            call_user_func($callback),
            3600
        );
    }
}
