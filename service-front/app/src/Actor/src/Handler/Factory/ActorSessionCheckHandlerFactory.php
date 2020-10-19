<?php

declare(strict_types=1);

namespace Actor\Handler\Factory;

use Actor\Handler\ActorSessionCheckHandler;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): ActorSessionCheckHandler
    {
        $config = $container->get('config');

        if (!isset($config['session']['expires'])) {
            throw new RuntimeException('Missing session expiry value');
        }

        return new ActorSessionCheckHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(AuthenticationInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(UrlHelper::class),
            $config['session']['expires']
        );
    }
}
