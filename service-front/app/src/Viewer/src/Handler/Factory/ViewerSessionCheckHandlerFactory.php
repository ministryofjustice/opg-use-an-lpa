<?php

declare(strict_types=1);

namespace Viewer\Handler\Factory;

use Viewer\Handler\ViewerSessionCheckHandler;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ViewerSessionCheckHandlerFactory
 * @package Viewer\Handler\Factory
 * @codeCoverageIgnore
 * Tests are covered by ActorSessionCheckHandlerFactory
 */
class ViewerSessionCheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): ViewerSessionCheckHandler
    {
        $config = $container->get('config');

        if (!isset($config['session']['expires'])) {
            throw new RuntimeException('Missing session expiry value');
        }

        return new ViewerSessionCheckHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(AuthenticationInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(UrlHelper::class),
            $config['session']['expires']
        );
    }
}
