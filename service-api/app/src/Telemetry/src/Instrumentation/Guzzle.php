<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use OpenTelemetry\SemConv\Attributes\ServerAttributes;
use OpenTelemetry\SemConv\Attributes\UrlAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\HttpIncubatingAttributes;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Telemetry\Suppression;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Creates a client span for every outbound Guzzle request and propagates the trace context to the remote
 * service. Client::transfer() is the single point that send(), sendAsync(), sendRequest() and request pools
 * all pass through.
 */
class Guzzle
{
    public const NAME = 'guzzle';

    private static CachedInstrumentation $instrumentation;

    public static function register(): void
    {
        if (Sdk::isInstrumentationDisabled(self::NAME)) {
            return;
        }

        self::$instrumentation = new CachedInstrumentation(
            'uk.gov.opg.use-an-lpa.guzzle',
            null,
            'https://opentelemetry.io/schemas/1.38.0',
        );

        hook(Client::class, 'transfer', pre: self::pre(...), post: self::post(...));
    }

    private static function pre(Client $client, array $params): ?array
    {
        $request = $params[0] ?? null;
        if (!$request instanceof RequestInterface || Suppression::isActive()) {
            return null;
        }

        $parentContext = Context::getCurrent();
        $uri           = $request->getUri();

        $span = self::$instrumentation->tracer()
            ->spanBuilder($uri->getHost() ?: 'unknown remote host')
            ->setParent($parentContext)
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute(UrlAttributes::URL_FULL, (string) $uri)
            ->setAttribute(UrlAttributes::URL_PATH, $uri->getPath())
            ->setAttribute(HttpAttributes::HTTP_REQUEST_METHOD, $request->getMethod())
            ->setAttribute(HttpIncubatingAttributes::HTTP_REQUEST_BODY_SIZE, $request->getBody()->getSize())
            ->setAttribute(ServerAttributes::SERVER_ADDRESS, $uri->getHost())
            ->setAttribute(ServerAttributes::SERVER_PORT, $uri->getPort())
            ->startSpan();

        $context = $span->storeInContext($parentContext);

        $headers = [];
        Globals::propagator()->inject($headers, null, $context);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        OwnedScope::attach(self::NAME, $context, $client);

        return [0 => $request];
    }

    private static function post(Client $client, array $params, ?PromiseInterface $promise, ?Throwable $exception): void
    {
        $span = OwnedScope::detach(self::NAME, $client);
        if ($span === null) {
            return;
        }

        if ($exception !== null || $promise === null) {
            self::endWithException($span, $exception);
            return;
        }

        $promise->then(
            static function (ResponseInterface $response) use ($span): ResponseInterface {
                self::endWithResponse($span, $response);

                return $response;
            },
            static function (mixed $reason) use ($span): void {
                self::endWithException($span, $reason instanceof Throwable ? $reason : null);
            },
        );
    }

    private static function endWithResponse(SpanInterface $span, ResponseInterface $response): void
    {
        $span->setAttribute(HttpAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());
        $span->setAttribute(
            HttpIncubatingAttributes::HTTP_RESPONSE_BODY_SIZE,
            $response->getHeaderLine('Content-Length'),
        );

        if ($response->getStatusCode() >= 500) {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }

        $span->end();
    }

    private static function endWithException(SpanInterface $span, ?Throwable $exception): void
    {
        if ($exception !== null) {
            $span->recordException($exception);
        }

        $span->setStatus(StatusCode::STATUS_ERROR, $exception?->getMessage());
        $span->end();
    }
}
