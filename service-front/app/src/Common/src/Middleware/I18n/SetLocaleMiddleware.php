<?php

declare(strict_types=1);

namespace Common\Middleware\I18n;

use Locale;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetLocaleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if ($request->getUri()->getPath() === '/cy') {
            Locale::setDefault('cy');
        } else {
            Locale::setDefault('en');
        }
        return $handler->handle($request);
    }
}
