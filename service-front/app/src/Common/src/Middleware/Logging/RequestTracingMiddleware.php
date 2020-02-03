<?php

declare(strict_types=1);

namespace Common\Middleware\Logging;

use Common\Service\Container\ModifiableContainerInterface;
use Common\Service\Log\RequestTracing;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RequestTracingMiddleware
 *
 * Ensures that the Nginx attached tracing header is correctly made available to the $request as an
 * attribute. It also puts that value into the DI container so it can be used by service factories
 * when instantiating their services.
 *
 * @package Common\Middleware\Logging
 */
class RequestTracingMiddleware implements MiddlewareInterface
{
    /**
     * @var ModifiableContainerInterface
     */
    private $container;

    public function __construct(ModifiableContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $traceId = $request->getHeader(RequestTracing::TRACE_HEADER_NAME);
        $traceId = (count($traceId) > 0) ? $traceId[0] : '';

        // for factories to use when making services.
        $this->container->setValue(RequestTracing::TRACE_PARAMETER_NAME, $traceId);

        return $delegate->handle($request->withAttribute(RequestTracing::TRACE_PARAMETER_NAME, $traceId));
    }
}
