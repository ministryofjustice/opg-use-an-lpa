<?php

declare(strict_types=1);

namespace App\Service\Cache\Symfony;

use DI\Factory\RequestedEntry;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use UnexpectedValueException;

final class TagAwareFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): TagAwareAdapter {
        if ($requestedName instanceof RequestedEntry) {
            $requestedName = $requestedName->getName();
        }

        $poolName = match ($requestedName) {
            'cache.request.taggable' => 'cache.request',
            'cache.app.taggable' => 'cache.app',
            default => throw new UnexpectedValueException('Unsupported tag-aware Symfony cache service'),
        };
        $pool = $container->get($poolName);

        if (!$pool instanceof AdapterInterface) {
            throw new UnexpectedValueException($poolName . ' must implement AdapterInterface');
        }

        return new TagAwareAdapter($pool);
    }
}
