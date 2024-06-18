<?php

declare(strict_types=1);

namespace Common\Handler\Factory;

use Common\Handler\GoneHandler;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class GoneHandlerFactory
{
    public function __invoke(ContainerInterface $container): GoneHandler
    {
        return new GoneHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
        );
    }
}
