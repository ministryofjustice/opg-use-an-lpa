<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Responsible for managing the expiry of user sessions.
 *
 * First, the session is checked to see if it has expired according to the recorded expiry time by comparing
 * to the current time. The pipeline is then run and then assuming the session has not been marked as expired
 * a new expiry time is written.
 */
class SessionExpiryMiddleware implements MiddlewareInterface
{
    /**
     * Key used within the session for the current time
     */
    public const SESSION_TIME_KEY = '__TIME__';

    /**
     * Key used within the session to flag that the session has been expired
     */
    public const SESSION_EXPIRED_KEY = '__EXPIRED__';

    public function __construct(private int $sessionExpiryTime)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
 * @var SessionInterface $session
*/
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        /**
 * @var RouteResult $routeResult
*/
        $routeResult = $request->getAttribute(RouteResult::class);

        $time = $session->get(self::SESSION_TIME_KEY);

        // responsible the for expiry of a users session
        $this->expireSession($session, $time);

        // Continue the running the application pipeline
        $response = $handler->handle($request);

        // Record the time if the session is not marked as expired
        $this->recordSessionTime($session, $routeResult, $time);

        return $response;
    }

    /**
     * If an expiry time is recorded in the session check to see if we've exceeded it and
     * mark the session as expired if we have.
     *
     * @param  SessionInterface $session
     * @param  int|null         $time
     * @return void
     */
    private function expireSession(SessionInterface $session, ?int $time): void
    {
        if ($time !== null) {
            $expiredOn = $time + $this->sessionExpiryTime;

            if (time() > $expiredOn) {
                // the session has expired and we're just learning about it.
                $session->set(self::SESSION_EXPIRED_KEY, true);
            }
        }
    }

    /**
     * Un-expire the session whilst recording a new expiry time to it.
     *
     * @param  SessionInterface $session
     * @param  RouteResult      $routeResult
     * @param  int|null         $currentSessionsExpiryTime
     * @return void
     */
    private function recordSessionTime(
        SessionInterface $session,
        RouteResult $routeResult,
        ?int $currentSessionsExpiryTime,
    ): void {
        // Clear session expiry marker if there is one.
        $session->unset(SessionExpiryMiddleware::SESSION_EXPIRED_KEY);

        $session->set(
            self::SESSION_TIME_KEY,
            $this->sessionTime(
                $currentSessionsExpiryTime,
                $routeResult->getMatchedRouteName()
            )
        );
    }

    /**
     * Depending on the accessed route we may want to either increment the session expiry appropriately or
     * return the time unchanged.
     *
     * @param  int|null     $currentSessionExpiryTime
     * @param  string|false $routeName
     * @return int
     */
    private function sessionTime(?int $currentSessionExpiryTime, string|false $routeName): int
    {
        return $currentSessionExpiryTime !== null && $routeName === 'session-check'
            ? $currentSessionExpiryTime
            : time();
    }
}
