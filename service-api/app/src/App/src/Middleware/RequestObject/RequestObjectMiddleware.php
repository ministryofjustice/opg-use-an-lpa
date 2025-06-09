<?php

declare(strict_types=1);

namespace App\Middleware\RequestObject;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestObjectMiddleware implements MiddlewareInterface
{
    public const REQUEST_OBJECT = 'requestObject';

    public function __construct(
        private readonly RequestParser $formatter,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->formatter->setRequestData($request->getParsedBody());

        return $handler->handle($request->withAttribute(self::REQUEST_OBJECT, $this->formatter));
    }
}
