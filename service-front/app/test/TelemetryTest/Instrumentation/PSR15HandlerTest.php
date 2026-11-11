<?php

declare(strict_types=1);

namespace TelemetryTest\Instrumentation;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OpenTelemetry\API\Trace\Span;
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
use Telemetry\IncomingTraceHeader;
use Telemetry\Instrumentation\PSR15Handler;

#[CoversClass(PSR15Handler::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PSR15HandlerTest extends TestCase
{
    #[Test]
    public function it_keeps_the_incoming_header_for_logging_without_reusing_its_root(): void
    {
        PSR15Handler::register();

        $sdkScope = Sdk::builder()
            ->setTracerProvider(new TracerProvider())
            ->setPropagator(new Propagator())
            ->buildAndRegisterGlobal();
        $previous = Context::getCurrent();
        $request  = new ServerRequest('GET', 'https://front.test/', [
            'x-amzn-trace-id' => 'Root=1-aaaaaaaa-aaaaaaaaaaaaaaaaaaaaaaaa',
        ]);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request): ResponseInterface {
                $context = Span::getCurrent()->getContext();

                self::assertTrue($context->isValid());
                self::assertNotSame(str_repeat('a', 32), $context->getTraceId());
                self::assertSame(
                    $request->getHeaderLine('x-amzn-trace-id'),
                    Context::getCurrent()->get(IncomingTraceHeader::key()),
                );

                return new Response();
            },
        );

        try {
            $handler->handle($request);

            self::assertSame($previous, Context::getCurrent());
            self::assertNull(Context::getCurrent()->get(IncomingTraceHeader::key()));
        } finally {
            $sdkScope->detach();
        }
    }
}
