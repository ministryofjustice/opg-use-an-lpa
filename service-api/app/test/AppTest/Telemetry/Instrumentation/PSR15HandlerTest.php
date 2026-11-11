<?php

declare(strict_types=1);

namespace AppTest\Telemetry\Instrumentation;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Telemetry\Instrumentation\PSR15Handler;

#[CoversClass(PSR15Handler::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PSR15HandlerTest extends TestCase
{
    #[Test]
    public function it_continues_the_incoming_trace_without_a_custom_id_generator(): void
    {
        PSR15Handler::register();

        $sdkScope = Sdk::builder()
            ->setTracerProvider(new TracerProvider())
            ->setPropagator(new Propagator())
            ->buildAndRegisterGlobal();
        $previous = Context::getCurrent();
        $request  = new ServerRequest('GET', 'https://api.test/', [
            'x-amzn-trace-id' => 'Root=1-aaaaaaaa-aaaaaaaaaaaaaaaaaaaaaaaa;Parent=bbbbbbbbbbbbbbbb;Sampled=1',
        ]);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request): ResponseInterface {
                $span = Span::getCurrent();

                self::assertSame($span, $request->getAttribute(SpanInterface::class));
                self::assertSame(str_repeat('a', 32), $span->getContext()->getTraceId());
                self::assertNotSame(str_repeat('b', 16), $span->getContext()->getSpanId());

                return new Response();
            },
        );

        try {
            $handler->handle($request);

            self::assertSame($previous, Context::getCurrent());
        } finally {
            $sdkScope->detach();
        }
    }
}
