<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use Laminas\Stratigility\Next;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use OpenTelemetry\SemConv\Attributes\NetworkAttributes;
use OpenTelemetry\SemConv\Attributes\ServerAttributes;
use OpenTelemetry\SemConv\Attributes\UrlAttributes;
use OpenTelemetry\SemConv\Attributes\UserAgentAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\HttpIncubatingAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Creates the root server span for an incoming request (continuing any propagated X-Ray/W3C trace) along
 * with internal spans for each application middleware and request handler executed.
 */
final class PSR15Handler
{
    public const string NAME = 'psr15';

    private const string MIDDLEWARE_HOOK = self::NAME . '.middleware';
    private const string HANDLER_HOOK    = self::NAME . '.handler';

    private static CachedInstrumentation $instrumentation;

    public static function register(): void
    {
        if (Sdk::isInstrumentationDisabled(self::NAME)) {
            return;
        }

        self::$instrumentation = new CachedInstrumentation(
            'uk.gov.opg.use-an-lpa.psr15',
            null,
            'https://opentelemetry.io/schemas/1.38.0',
        );

        hook(MiddlewareInterface::class, 'process', pre: self::middlewarePre(...), post: self::middlewarePost(...));
        hook(RequestHandlerInterface::class, 'handle', pre: self::handlerPre(...), post: self::handlerPost(...));
    }

    private static function middlewarePre(
        MiddlewareInterface $middleware,
        array $params,
        string $class,
        string $function,
        ?string $filename,
        ?int $lineno,
    ): void {
        if (self::isFrameworkMiddleware($middleware)) {
            return;
        }

        $span = self::$instrumentation->tracer()
            ->spanBuilder(sprintf('%s::%s', $class, $function))
            ->setAttribute(CodeAttributes::CODE_FUNCTION_NAME, sprintf('%s::%s', $class, $function))
            ->setAttribute(CodeAttributes::CODE_FILE_PATH, $filename)
            ->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $lineno)
            ->startSpan();

        OwnedScope::attach(self::MIDDLEWARE_HOOK, $span->storeInContext(Context::getCurrent()), $middleware);
    }

    private static function middlewarePost(
        MiddlewareInterface $middleware,
        array $params,
        ?ResponseInterface $response,
        ?Throwable $exception,
    ): void {
        $span = OwnedScope::detach(self::MIDDLEWARE_HOOK, $middleware);
        if ($span === null) {
            return;
        }

        self::recordException($span, $exception);
        $span->end();
    }

    /**
     * The first RequestHandlerInterface::handle executed is treated as the root span, which is stored as a
     * request attribute so that subsequent handlers know to create child spans.
     */
    private static function handlerPre(
        RequestHandlerInterface $handler,
        array $params,
        string $class,
        string $function,
        ?string $filename,
        ?int $lineno,
    ): ?array {
        if ($handler instanceof Next) {
            return null;
        }

        $request = ($params[0] ?? null) instanceof ServerRequestInterface ? $params[0] : null;
        $isRoot  = $request !== null && $request->getAttribute(SpanInterface::class) === null;

        $builder = self::$instrumentation->tracer()
            ->spanBuilder($isRoot ? $request->getMethod() : sprintf('%s::%s', $class, $function))
            ->setAttribute(CodeAttributes::CODE_FUNCTION_NAME, sprintf('%s::%s', $class, $function))
            ->setAttribute(CodeAttributes::CODE_FILE_PATH, $filename)
            ->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $lineno);

        $parent = Context::getCurrent();
        if ($isRoot) {
            $parent  = Globals::propagator()->extract($request->getHeaders());
            $span    = self::withServerAttributes($builder->setParent($parent), $request)->startSpan();
            $request = $request->withAttribute(SpanInterface::class, $span);
        } else {
            $span = $builder->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        }

        OwnedScope::attach(self::HANDLER_HOOK, $span->storeInContext($parent), $handler);

        return $request !== null ? [0 => $request] : null;
    }

    private static function handlerPost(
        RequestHandlerInterface $handler,
        array $params,
        ?ResponseInterface $response,
        ?Throwable $exception,
    ): void {
        $span = OwnedScope::detach(self::HANDLER_HOOK, $handler);
        if ($span === null) {
            return;
        }

        self::recordException($span, $exception);

        if ($response !== null) {
            if ($response->getStatusCode() >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR);
            }

            $span->setAttribute(HttpAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());
            $span->setAttribute(NetworkAttributes::NETWORK_PROTOCOL_VERSION, $response->getProtocolVersion());
            $span->setAttribute(
                HttpIncubatingAttributes::HTTP_RESPONSE_BODY_SIZE,
                $response->getHeaderLine('Content-Length'),
            );
        }

        $span->end();
    }

    private static function isFrameworkMiddleware(MiddlewareInterface $middleware): bool
    {
        return str_starts_with($middleware::class, 'Laminas\\') || str_starts_with($middleware::class, 'Mezzio\\');
    }

    private static function withServerAttributes(
        SpanBuilderInterface $builder,
        ServerRequestInterface $request,
    ): SpanBuilderInterface {
        $uri = $request->getUri();

        return $builder
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttribute(UrlAttributes::URL_FULL, (string) $uri)
            ->setAttribute(UrlAttributes::URL_SCHEME, $uri->getScheme())
            ->setAttribute(UrlAttributes::URL_PATH, $uri->getPath())
            ->setAttribute(HttpAttributes::HTTP_REQUEST_METHOD, $request->getMethod())
            ->setAttribute(HttpIncubatingAttributes::HTTP_REQUEST_BODY_SIZE, $request->getHeaderLine('Content-Length'))
            ->setAttribute(UserAgentAttributes::USER_AGENT_ORIGINAL, $request->getHeaderLine('User-Agent'))
            ->setAttribute(ServerAttributes::SERVER_ADDRESS, $uri->getHost())
            ->setAttribute(ServerAttributes::SERVER_PORT, $uri->getPort());
    }

    private static function recordException(SpanInterface $span, ?Throwable $exception): void
    {
        if ($exception !== null) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }
    }
}
