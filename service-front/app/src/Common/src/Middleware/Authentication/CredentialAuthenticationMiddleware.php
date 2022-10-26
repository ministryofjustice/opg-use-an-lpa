<?php

declare(strict_types=1);

namespace Common\Middleware\Authentication;

use Common\Middleware\Session\SessionAttributeAllowlistMiddleware;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CredentialAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(protected AuthenticationInterface $auth, protected UrlHelper $helper)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (! $session) {
            throw MissingSessionContainerException::create();
        }

        // if an expiry key has been set then it is the case we should direct user to the session-expired page
        if ($session->has(SessionExpiryMiddleware::SESSION_EXPIRED_KEY)) {
            return new RedirectResponse($this->helper->generate('session-expired'));
        }

        $user = $this->auth->authenticate($request);

        if (null !== $user) {
            $response = $handler->handle($request->withAttribute(UserInterface::class, $user));

            // something in the handler has removed the users login (probably logout) ensure we tell the
            // session handling code to strip everything out.
            if (! $session->has(UserInterface::class)) {
                $session->set(SessionAttributeAllowlistMiddleware::SESSION_CLEAN_NEEDED, true);
            }

            return $response;
        }

        return $this->auth->unauthorizedResponse($request);
    }
}
