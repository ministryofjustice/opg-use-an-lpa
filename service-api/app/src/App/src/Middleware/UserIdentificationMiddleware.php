<?php

namespace App\Middleware;

use App\Exception\AbstractApiException;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;


class UserIdentificationMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        return $delegate->handle($request->withAttribute('user-id', 'user-1'));
    }
}
