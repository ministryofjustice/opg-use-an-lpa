<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Uri;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use DateTime;

class SessionExpiredRedirectMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlHelper
     */
    private $helper;

    public function __construct(UrlHelper $urlHelper)
    {
        $this->helper = $urlHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($session !== null && $session->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)) {

            $sessionExpiredDatetime = $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY);

            // extracting the dates alone to do a date comparison to check if expire date was the previous day
            // converting the Date string format to int to reaffirm comparison
            $currentDate = intval((new DateTime())->format('Ymd'));
            $expiredDate = intval((new DateTime("@$sessionExpiredDatetime"))->format('Ymd'));

            //  to compare time elapsed
            $currentEpochTime = $epoch = time();
            $expiredEpochTime = $sessionExpiredDatetime;
            $timeElapsed = ($currentEpochTime - $expiredEpochTime) / 60;

            $session->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY);
            if ($currentDate == $expiredDate and $timeElapsed >= 20) {
                $uri = new Uri($this->helper->generate('session-expired'));
            } else {
                $uri = new Uri($this->helper->generate('home'));
            }
            return new RedirectResponse($uri);
        } else {
            return $handler->handle($request);
        }
    }
}
