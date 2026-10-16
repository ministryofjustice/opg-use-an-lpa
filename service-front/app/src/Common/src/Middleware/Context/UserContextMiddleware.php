<?php

declare(strict_types=1);

namespace Common\Middleware\Context;

use Common\Service\Context\UserContext;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionIdentifierAwareInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UserContextMiddleware implements MiddlewareInterface
{
    public function __construct(private UserContext $context)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->reset();

        try {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            if (!$session instanceof SessionInterface || !$session instanceof SessionIdentifierAwareInterface) {
                throw MissingSessionContainerException::create();
            }

            $this->context->sessionId = $session->getId();
            // the userId is populated by the AuthenticationMiddleware on a successful authentication process

            return $handler->handle($request->withAttribute(UserContext::class, $this->context));
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        $this->context->userId    = null;
        $this->context->sessionId = null;
    }
}
