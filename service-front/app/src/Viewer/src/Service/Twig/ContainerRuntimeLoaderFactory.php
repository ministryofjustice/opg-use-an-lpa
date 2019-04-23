<?php

declare(strict_types=1);

namespace Viewer\Service\Twig;

use Psr\Container\ContainerInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

class ContainerRuntimeLoaderFactory
{

    public function __invoke(ContainerInterface $container) : ContainerRuntimeLoader
    {
        return new ContainerRuntimeLoader($container);
    }
}