<?php

declare(strict_types=1);

use Common\Middleware\I18n\SetLocaleMiddleware;
use Common\Middleware\Logging\RequestTracingMiddleware;
use Common\Middleware\Security\CSPNonceMiddleware;
use Common\Middleware\Security\RateLimitMiddleware;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Middleware\Session\SessionAttributeAllowlistMiddleware;
use Common\Middleware\Session\SessionExpiryMiddleware;
use Common\Middleware\Session\SessionTimeoutMiddleware;
use Common\Middleware\Workflow\StatePersistenceMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Session\SessionMiddleware;
use Psr\Container\ContainerInterface;

/**
 * Setup middleware pipeline:
 */

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    // The error handler should be the first (most outer) middleware to catch
    // all Exceptions.
    $app->pipe(ErrorHandler::class);
    $app->pipe(SessionTimeoutMiddleware::class);
    $app->pipe(ServerUrlMiddleware::class);

    // Pipe more middleware here that you want to execute on every request:
    // - bootstrapping
    // - pre-conditions
    // - modifications to outgoing responses
    //
    // Piped Middleware may be either callables or service names. Middleware may
    // also be passed as an array; each item in the array must resolve to
    // middleware eventually (i.e., callable or service name).
    //
    // Middleware can be attached to specific paths, allowing you to mix and match
    // applications under a common domain.  The handlers in each middleware
    // attached this way will see a URI with the matched path segment removed.
    //
    // i.e., path of "/api/member/profile" only passes "/member/profile" to $apiMiddleware
    // - $app->pipe('/api', $apiMiddleware);
    // - $app->pipe('/docs', $apiDocMiddleware);
    // - $app->pipe('/files', $filesMiddleware);

    $app->pipe(RequestTracingMiddleware::class);

    // Discern the intended locale
    $app->pipe(SetLocaleMiddleware::class);

    // Register the routing middleware in the middleware pipeline.
    // This middleware registers the Mezzio\Router\RouteResult request attribute.
    $app->pipe(RouteMiddleware::class);

    // Load session from request and save it on the return
    $app->pipe(SessionMiddleware::class);
    $app->pipe(SessionExpiryMiddleware::class);

    $app->pipe(UserIdentificationMiddleware::class);
    $app->pipe(RateLimitMiddleware::class);

    $app->pipe(FlashMessageMiddleware::class);

    // Clean out the session if expired
    $app->pipe(SessionAttributeAllowlistMiddleware::class);

    $app->pipe(CsrfMiddleware::class);

    $app->pipe(CSPNonceMiddleware::class);

    $app->pipe(StatePersistenceMiddleware::class);

    // The following handle routing failures for common conditions:
    // - HEAD request but no routes answer that method
    // - OPTIONS request but no routes answer that method
    // - method not allowed
    // Order here matters; the MethodNotAllowedMiddleware should be placed
    // after the Implicit*Middleware.
    $app->pipe(ImplicitHeadMiddleware::class);
    $app->pipe(ImplicitOptionsMiddleware::class);
    $app->pipe(MethodNotAllowedMiddleware::class);

    // Seed the UrlHelper with the routing results:
    $app->pipe(UrlHelperMiddleware::class);

    // Add more middleware here that needs to introspect the routing results; this
    // might include:
    //
    // - route-based authentication
    // - route-based validation
    // - etc.

    // Register the dispatch middleware in the middleware pipeline
    $app->pipe(DispatchMiddleware::class);

    $app->pipe(\Common\Middleware\ErrorHandling\GoneHandler::class);

    // At this point, if no Response is returned by any middleware, the
    // NotFoundHandler kicks in; alternately, you can provide other fallback
    // middleware to execute.
    $app->pipe(NotFoundHandler::class);
};
