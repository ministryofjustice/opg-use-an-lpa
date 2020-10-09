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

class ViewerSessionCheckHandlerFactory
{
    public function __invoke(
        ContainerInterface $container,
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
        UrlHelper $urlHelper
    ): ViewerSessionCheckHandler {

        $config = $container->get('config');

        if (!isset($config['session']['expires'])) {
            throw new RuntimeException('Missing session expiry value');
        }

        return new ViewerSessionCheckHandler(
            $renderer,
            $authenticator,
            $logger,
            $urlHelper,
            $config['session']['expires']
        );
    }
}
