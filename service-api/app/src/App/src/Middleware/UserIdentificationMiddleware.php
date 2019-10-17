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
    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $userId = $request->getHeader('User-Token');

        if (isset($userId[0])) {
            return $delegate->handle($request->withAttribute('actor-id', $userId[0]));
        }

        throw new UnauthorizedException('User-Token not specified or invalid');
    }
}
