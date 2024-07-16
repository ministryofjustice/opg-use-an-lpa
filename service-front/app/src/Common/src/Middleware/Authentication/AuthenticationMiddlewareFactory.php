<?php

declare(strict_types=1);

namespace Common\Middleware\Authentication;

use Common\Middleware\Authentication\AuthenticationMiddleware;
use Common\Middleware\Authentication\CredentialAuthenticationMiddleware;
use Common\Middleware\Authentication\ForcedPasswordResetMiddleware;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Container\ContainerInterface;

class AuthenticationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationMiddleware
    {
        $pipe                               = $container->get(MiddlewarePipeInterface::class);
        $credentialAuthenticationMiddleware = $container->get(CredentialAuthenticationMiddleware::class);
        $forcedPasswordResetMiddleware      = $container->get(ForcedPasswordResetMiddleware::class);

        $feature_flags = $container->get('config')['feature_flags'];
        $middlewares   = [$credentialAuthenticationMiddleware];

        if (!($feature_flags['allow_gov_one_login'] ?? false)) {
            $middlewares[] = $forcedPasswordResetMiddleware;
        }

        return new AuthenticationMiddleware($pipe, ...$middlewares);
    }
}
