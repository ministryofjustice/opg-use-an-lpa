<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\AuditableLpasInterface;
use App\Service\Cache\CacheFactory;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class CachedDataStoreLpasDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): AuditableLpasInterface {
        $cacheFactory = $container->get(CacheFactory::class);

        return new CachedDataStoreLpas(
            /** @var DataStoreLpas */
            call_user_func($callback),
            ($cacheFactory)('request'),
            $container->get(LoggerInterface::class),
        );
    }
}
