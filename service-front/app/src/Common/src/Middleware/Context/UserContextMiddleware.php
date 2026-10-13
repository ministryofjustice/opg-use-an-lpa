<?php

declare(strict_types=1);

namespace Common\Middleware\Context;

use Common\Service\Context\UserContext;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserContextMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $this->container->get(UserContext::class);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $context->sessionId = $session->getId();

        if (($user = $request->getAttribute(UserInterface::class)) !== null) {
            $context->userId = $user->getIdentity();
        }

        return $handler->handle($request->withAttribute(UserContext::class, $context));
    }
}
