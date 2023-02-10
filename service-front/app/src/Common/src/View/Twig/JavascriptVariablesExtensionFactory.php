<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\Security\CSPNonce;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

class JavascriptVariablesExtensionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['analytics']['uaid'])) {
            throw new UnexpectedValueException('Missing google analytics ua id');
        }

        return new JavascriptVariablesExtension(
            $container->get(CSPNonce::class),
            $config['analytics']['uaid'],
        );
    }
}
