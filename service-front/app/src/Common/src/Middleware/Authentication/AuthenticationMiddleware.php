<?php

declare(strict_types=1);

namespace Common\Middleware\Authentication;

use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\Authentication\AuthenticationMiddleware as MezzioAuthenticationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    private MiddlewarePipeInterface $pipe;

    public function __construct(
        MiddlewarePipeInterface $middlewarePipe,
        MezzioAuthenticationMiddleware $authenticationMiddleware,
        ForcedPasswordResetMiddleware $forcedPasswordResetMiddleware
    ) {
        $this->pipe = $middlewarePipe;
        $this->pipe->pipe($authenticationMiddleware);
        $this->pipe->pipe($forcedPasswordResetMiddleware);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->pipe->process($request, $handler);
    }
}
