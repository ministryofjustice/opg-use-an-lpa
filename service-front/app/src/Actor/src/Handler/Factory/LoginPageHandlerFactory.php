<?php

declare(strict_types=1);

namespace Actor\Handler\Factory;

use Common\Service\Security\RateLimitServiceFactory;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Actor\Handler\LoginPageHandler;

class LoginPageHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $rateLimitFactory = $container->get(RateLimitServiceFactory::class);

        return new LoginPageHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(AuthenticationInterface::class),
            $container->get(LoggerInterface::class),
            $rateLimitFactory->factory('actor_login_failure')
        );
    }
}
