<?php

declare(strict_types=1);

namespace Actor\Handler\Factory;

use Actor\Handler\CheckLpaHandler;
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitServiceFactory;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Acpr\I18n\TranslatorInterface;

class CheckLpaHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $rateLimitFactory = $container->get(RateLimitServiceFactory::class);

        return new CheckLpaHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(AuthenticationInterface::class),
            $container->get(LpaService::class),
            $container->get(LoggerInterface::class),
            $rateLimitFactory->factory('actor_code_failure'),
            $container->get(TranslatorInterface::class)
        );
    }
}
