<?php

declare(strict_types=1);

namespace BehatTest\Common\Service\Cache;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

final class TagAwareArrayPoolFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): TagAwareAdapter {
        return new TagAwareAdapter(new ArrayAdapter());
    }
}
