<?php

declare(strict_types=1);

namespace AppTest\Telemetry\Instrumentation;

use Aws\EventBridge\EventBridgeClient;
use Aws\MockHandler;
use Aws\Result;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Telemetry\Instrumentation\Aws;

#[CoversClass(Aws::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AwsTest extends TestCase
{
    #[Test]
    public function it_attaches_the_active_client_trace_to_an_event(): void
    {
        $sdkScope = Sdk::builder()
            ->setTracerProvider(new TracerProvider())
            ->setPropagator(new Propagator())
            ->buildAndRegisterGlobal();

        Aws::register();

        $parentContext = Span::wrap(
            SpanContext::create(str_repeat('a', 32), str_repeat('b', 16), TraceFlags::SAMPLED),
        )->storeInContext(Context::getCurrent());
        $parentScope   = $parentContext->activate();
        $handler       = new MockHandler([new Result(['FailedEntryCount' => 0])]);
        $client        = new EventBridgeClient([
            'region'      => 'eu-west-1',
            'version'     => 'latest',
            'credentials' => false,
            'handler'     => $handler,
        ]);
        $entry         = [
            'Source'       => 'opg.poas.use',
            'DetailType'   => 'activation-key-requested',
            'Detail'       => '{"uid":"M-7890-0400-4000"}',
            'EventBusName' => 'test-bus',
        ];
        try {
            $client->putEvents(['Entries' => [$entry]]);

            $sentEntry = $handler->getLastCommand()['Entries'][0];
            $header    = $sentEntry['TraceHeader'];

            self::assertMatchesRegularExpression(
                '/^Root=1-aaaaaaaa-aaaaaaaaaaaaaaaaaaaaaaaa;Parent=[0-9a-f]{16};Sampled=1$/',
                $header,
            );
            self::assertStringNotContainsString('Parent=' . str_repeat('b', 16), $header);

            unset($sentEntry['TraceHeader']);
            self::assertSame($entry, $sentEntry);

            self::assertSame($parentContext, Context::getCurrent());
        } finally {
            $parentScope->detach();
            $sdkScope->detach();
        }
    }
}
