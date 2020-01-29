<?php

declare(strict_types=1);

namespace Common\Middleware\Logging;

use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RequestTracingMiddleware
 *
 * Ensures that the Nginx attached tracing header is correctly made available to the $request as an
 * attribute and that the response has that same header attached so that upstream logs can aggregate it.
 *
 * @package Common\Middleware\Logging
 */
class RequestTracingMiddleware implements MiddlewareInterface
{
    const TRACE_HEADER_NAME = 'x-amzn-trace-id';

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $traceId = $request->getHeader(self::TRACE_HEADER_NAME);
        $traceId = (count($traceId) > 0) ? $traceId[0] : '';

        return $delegate->handle($request->withAttribute('amzn-trace-id', $traceId));
    }
}
