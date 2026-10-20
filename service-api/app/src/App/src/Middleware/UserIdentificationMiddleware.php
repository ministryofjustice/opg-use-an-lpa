<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\UnauthorizedException;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserIdentificationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $handler): ResponseInterface
    {
        $userId      = $request->getHeader('User-Token');
        $userSession = $request->getHeader('User-Session');

        if (isset($userId[0]) && isset($userSession[0])) {
            return $handler->handle(
                $request
                    ->withAttribute('actor-id', $userId[0])
                    ->withAttribute('actor-session', $userSession[0])
            );
        }

        throw new UnauthorizedException('User-Token not specified or invalid');
    }
}
