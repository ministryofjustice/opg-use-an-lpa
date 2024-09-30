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
        private MiddlewarePipeInterface $pipe,
        MiddlewareInterface ...$middlewares,
    ) {
        foreach ($middlewares as $middleware) {
            $this->pipe->pipe($middleware);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->pipe->process($request, $handler);
    }
}
