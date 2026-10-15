<?php

declare(strict_types=1);

namespace Common\Middleware\Twig;

use Common\Entity\User;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Common\View\Twig\RequestGlobalsRuntime;
use Mezzio\Authentication\Session\Exception\MissingSessionContainerException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class TwigRequestGlobalsMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestGlobalsRuntime $runtime)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (!$session instanceof SessionInterface) {
            throw MissingSessionContainerException::create();
        }

        $flashMessages = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        if (!$flashMessages instanceof FlashMessagesInterface) {
            throw new RuntimeException('Flash message middleware must run before Twig request globals middleware');
        }

        $this->runtime->reset();

        try {
            $this->runtime->setUser($this->getUser($session));
            $this->runtime->setFlashMessages($flashMessages);

            return $handler->handle($request);
        } finally {
            $this->runtime->reset();
        }
    }

    private function getUser(SessionInterface $session): ?UserInterface
    {
        if ($session->has(SessionExpiryMiddleware::SESSION_EXPIRED_KEY)) {
            return null;
        }

        $userInfo = $session->get(UserInterface::class);
        if (!is_array($userInfo) || !isset($userInfo['username'])) {
            return null;
        }

        // clone user so it can't affect the session based one
        return new User($userInfo['username'], $userInfo['roles'] ?? [], $userInfo['details'] ?? []);
    }
}
