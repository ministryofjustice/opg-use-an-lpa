<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Viewer\Service\Lpa\LpaService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class ViewLpaHandlerFactory
 * @package Viewer\Handler
 */
class ViewLpaHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container) : RequestHandlerInterface
    {
        $renderer = $container->get(TemplateRendererInterface::class);
        $urlHelper = $container->get(UrlHelper::class);
        $lpaService = $container->get(LpaService::class);

        return new ViewLpaHandler($renderer, $urlHelper, $lpaService);
    }
}
