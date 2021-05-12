<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Session;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActorSessionExpiredHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ActorSessionExpiredHandler extends AbstractHandler implements SessionAware
{
    use Session;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If we're viewing this page then ensure our session is always expired.
        // This effectively makes this page acts to logout the user but ensures that we don't end up in
        // redirect loops around the session handling code.
        $this
            ->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE)
            ->set(EncryptedCookiePersistence::SESSION_EXPIRED_KEY, true);

        return new HtmlResponse($this->renderer->render('actor::actor-session-expired'));
    }
}
