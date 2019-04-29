<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Viewer\Service\Lpa\LpaService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Helper\UrlHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class EnterCodeHandlerFactory
 * @package Viewer\Handler
 */
class EnterCodeHandlerFactory
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
        $formFactory = $container->get(FormFactoryInterface::class);

        return new EnterCodeHandler($renderer, $urlHelper, $lpaService, $formFactory);
    }
}
