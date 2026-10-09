<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\AuditableLpasInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CachedDataStoreLpasDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): AuditableLpasInterface {
        return new CachedDataStoreLpas(
            /** @var DataStoreLpas */
            call_user_func($callback),
            $container->get(LoggerInterface::class),
        );
    }
}
