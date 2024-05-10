<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

use App\Service\Cache\CacheFactory;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;

class CachedSystemMessageDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): SystemMessageService {
        $cacheFactory = $container->get(CacheFactory::class);

        return new CachedSystemMessage(
            /** @var SystemMessageService */
            call_user_func($callback),
            ($cacheFactory)('system-message'),
        );
    }
}
