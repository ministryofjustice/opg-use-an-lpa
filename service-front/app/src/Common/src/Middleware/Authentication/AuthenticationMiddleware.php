<?php

declare(strict_types=1);

namespace Common\Middleware\Authentication;

use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        ContainerInterface $container,
        private MiddlewarePipeInterface $pipe,
        CredentialAuthenticationMiddleware $authenticationMiddleware,
        ForcedPasswordResetMiddleware $forcedPasswordResetMiddleware,
    ) {
        $feature_flags = $container->get('config')['feature_flags'];

        $this->pipe->pipe($authenticationMiddleware);

        if (!($feature_flags['allow_gov_one_login'] ?? false)) {
            $this->pipe->pipe($forcedPasswordResetMiddleware);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->pipe->process($request, $handler);
    }
}
