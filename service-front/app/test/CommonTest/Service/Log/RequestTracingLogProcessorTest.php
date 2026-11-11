<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\RequestTracing;
use Common\Service\Log\RequestTracingLogProcessor;
use DI\NotFoundException;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(RequestTracingLogProcessor::class)]
final class RequestTracingLogProcessorTest extends TestCase
{
    /**
     * The log processor uses the OTEL SDK to inject the trace context into the log record and the SDK
     * is made up of static accessors so we have to use real SDK objects to test it.
     */
    #[Test]
    #[DataProvider('samplingFlags')]
    public function it_should_read_the_active_context_for_each_record(int $flags): void
    {
        $header = 'Root=1-67891233-abcdef012345678912345678';

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(RequestTracing::TRACE_PARAMETER_NAME)->willReturn($header);

        $processor = new RequestTracingLogProcessor($container);
        $record    = [
            'message' => 'example',
            'extra'   => ['file' => 'example.php'],
        ];

        foreach (['53995c3f42cd8ad8', '63995c3f42cd8ad8'] as $spanId) { #pragma: allowlist secret
            $scope = Span::wrap(SpanContext::create(str_repeat('a', 32), $spanId, $flags))->activate();
            try {
                $result = $processor($record);
                self::assertSame(
                    'Root=1-aaaaaaaa-aaaaaaaaaaaaaaaaaaaaaaaa;Parent=' . $spanId . ';Sampled=' . $flags,
                    $result['extra']['trace_id'],
                );
                self::assertArrayNotHasKey('span_id', $result['extra']);
                self::assertSame($header, $result['extra']['alb_trace_id']);
                self::assertSame('example', $result['message']);
                self::assertSame('example.php', $result['extra']['file']);
            } finally {
                $scope->detach();
            }
        }

        $scope = Context::getRoot()->activate();
        try {
            self::assertSame(
                [
                    'message' => 'example',
                    'extra'   => [
                        'file'         => 'example.php',
                        'alb_trace_id' => $header,
                    ],
                ],
                $processor($record),
            );
        } finally {
            $scope->detach();
        }
    }

    public static function samplingFlags(): array
    {
        return [
            'sampled'   => [TraceFlags::SAMPLED],
            'unsampled' => [TraceFlags::DEFAULT],
        ];
    }

    #[Test]
    public function it_should_omit_identifiers_when_no_context_or_header_is_available(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->willThrowException(new NotFoundException('No request yet'));
        $scope = Context::getRoot()->activate();

        try {
            self::assertSame([], (new RequestTracingLogProcessor($container))([]));
        } finally {
            $scope->detach();
        }
    }

    #[Test]
    public function it_should_omit_an_empty_incoming_header(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->willReturn('');
        $scope = Context::getRoot()->activate();

        try {
            self::assertSame([], (new RequestTracingLogProcessor($container))([]));
        } finally {
            $scope->detach();
        }
    }
}
