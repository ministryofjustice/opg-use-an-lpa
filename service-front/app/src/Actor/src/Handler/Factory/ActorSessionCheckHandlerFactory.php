<?php

declare(strict_types=1);

namespace Actor\Handler\Factory;

use Common\Handler\SessionCheckHandler;
use Mezzio\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionCheckHandler
    {
        $config = $container->get('config');

        if (!isset($config['session']['expires'])) {
            throw new RuntimeException('Missing session expiry value');
        }

        if (!isset($config['session']['expiry_warning'])) {
            throw new RuntimeException('Missing session expiry warning value');
        }

        return new SessionCheckHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(LoggerInterface::class),
            $config['session']['expires'],
            $config['session']['expiry_warning']
        );
    }
}
