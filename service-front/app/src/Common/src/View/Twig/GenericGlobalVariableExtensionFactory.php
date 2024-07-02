<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Acpr\I18n\TranslatorInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class GenericGlobalVariableExtensionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $translator = $container->get(TranslatorInterface::class);

        if (!isset($config['application'])) {
            throw new RuntimeException('Missing application type, should be one of "viewer" or "actor"');
        }

        return new GenericGlobalVariableExtension(
            $config['application'],
            $translator
        );
    }
}
