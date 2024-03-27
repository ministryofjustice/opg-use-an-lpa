<?php

declare(strict_types=1);

namespace Actor\Handler\Factory;

use Actor\Handler\LogoutPageHandler;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Authentication\LocalAccountLogout;
use Common\Service\OneLogin\OneLoginService;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LogoutPageHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogoutPageHandler
    {
        $logoutStrategy = $container->get(
            ($container->get(FeatureEnabled::class))('allow_gov_one_login')
                ? OneLoginService::class
                : LocalAccountLogout::class,
        );

        return new LogoutPageHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UrlHelper::class),
            $container->get(AuthenticationInterface::class),
            $container->get(LoggerInterface::class),
            $logoutStrategy,
        );
    }
}
