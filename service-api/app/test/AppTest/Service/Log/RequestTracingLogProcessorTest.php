<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\RequestTracingLogProcessor;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
        $processor = new RequestTracingLogProcessor();
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
                self::assertArrayNotHasKey('alb_trace_id', $result['extra']);
                self::assertSame('example', $result['message']);
                self::assertSame('example.php', $result['extra']['file']);
            } finally {
                $scope->detach();
            }
        }

        $scope = Context::getRoot()->activate();
        try {
            self::assertSame($record, $processor($record));
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
    public function it_should_omit_identifiers_when_no_context_is_available(): void
    {
        $scope = Context::getRoot()->activate();

        try {
            self::assertSame([], (new RequestTracingLogProcessor())([]));
        } finally {
            $scope->detach();
        }
    }
}
