<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Service\Security\CSPNonce;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CSPNonceMiddleware implements MiddlewareInterface
{
    public const NONCE_ATTRIBUTE = 'csp-nonce';

    public function __construct(private CSPNonce $nonce)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request->withAttribute(self::NONCE_ATTRIBUTE, $this->nonce)
        )->withAddedHeader(
            'X-CSP-Nonce',
            'nonce-' . $this->nonce,
        );
    }
}
