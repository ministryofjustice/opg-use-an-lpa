<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use App\Service\Cache\CacheFactory;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;

class CachedSecretManagerDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): SecretManagerInterface {
        $cacheFactory = $container->get(CacheFactory::class);

        return new CachedSecretManager(
            ($cacheFactory)($name),
            /** @var SecretManagerInterface */
            call_user_func($callback),
            3600
        );
    }
}
