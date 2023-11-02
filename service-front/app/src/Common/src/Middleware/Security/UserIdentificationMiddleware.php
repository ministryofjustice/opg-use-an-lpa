<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Service\Security\UserIdentificationService;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Attempts to uniquely identify the user of an application for the purposes of throttling and brute force
 * protection.
 */
class UserIdentificationMiddleware implements MiddlewareInterface
{
    public const IDENTIFY_ATTRIBUTE = 'identity';

    /**
     * A list of route names that should bypass identification since browsers are inconsistent about the headers that
     * are sent when using the javascript fetch api.
     */
    public const EXCLUDED_ROUTES = [
        'session-check',
        'session-refresh',
        'session-expired',
    ];

    public function __construct(
        private UserIdentificationService $identificationService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
 * @var SessionInterface|null $session 
*/
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $id = $session?->get(self::IDENTIFY_ATTRIBUTE);

        // Only check identity on valid non-excluded routes
        if ($this->isValidRoute($request)) {
            $id = $this->identificationService->id($request->getHeaders(), $id)->hash();
            $session?->set(self::IDENTIFY_ATTRIBUTE, $id);
        }

        return $handler->handle($request->withAttribute(self::IDENTIFY_ATTRIBUTE, $id));
    }

    private function isValidRoute(ServerRequestInterface $request): bool
    {
        /**
 * @var RouteResult|null $routeResult 
*/
        $routeResult = $request->getAttribute(RouteResult::class);

        $accept = $request->hasHeader('accept') ? $request->getHeader('accept')[0] : null;

        if (in_array($routeResult?->getMatchedRouteName(), self::EXCLUDED_ROUTES)
            && $accept === 'application/json'
        ) {
            return false;
        }

        return true;
    }
}
