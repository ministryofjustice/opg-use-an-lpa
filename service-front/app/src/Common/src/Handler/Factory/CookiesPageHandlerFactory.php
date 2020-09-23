<?php

declare(strict_types=1);

namespace Common\Handler\Factory;

use Common\Handler\CookiesPageHandler;
use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class CookiesPageHandlerFactory
{
    public function __invoke(ContainerInterface $container): CookiesPageHandler
    {
        $config = $container->get('config');

        if (!isset($config['application'])) {
            throw new RuntimeException('Missing application type, should be one of "viewer" or "actor"');
        }

        return new CookiesPageHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(UrlValidityCheckService::class),
            $config['application']
        );
    }
}
