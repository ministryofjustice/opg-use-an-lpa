<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Psr\Container\ContainerInterface;

class GenericGlobalVariableExtensionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['application'])) {
            throw new \RuntimeException('Missing application type, should be one of "viewer" or "actor"');
        }

        return new GenericGlobalVariableExtension(
            $config['application']
        );
    }
}
