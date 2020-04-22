<?php

declare(strict_types=1);

namespace Viewer\Handler\Factory;

use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitServiceFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Viewer\Handler\CheckCodeHandler;

class CheckCodeHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $rateLimitFactory = $container->get(RateLimitServiceFactory::class);

        return new CheckCodeHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(LpaService::class),
            $rateLimitFactory->factory('viewer_code_failure')
        );
    }
}