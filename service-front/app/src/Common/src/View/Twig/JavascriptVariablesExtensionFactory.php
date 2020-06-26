<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Psr\Container\ContainerInterface;
use RuntimeException;

class JavascriptVariablesExtensionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['analytics']['uaid'])) {
            throw new RuntimeException('Missing google analytics ua id');
        }

        return new JavascriptVariablesExtension(
            $config['analytics']['uaid']
        );
    }
}
