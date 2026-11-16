<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use Aws\AwsClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\EventBridge\EventBridgeClient;
use Aws\ResultInterface;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use Telemetry\Suppression;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Creates a client span for every AWS SDK operation and propagates it in EventBridge event entries.
 */
final class Aws
{
    public const string NAME = 'aws';

    private static CachedInstrumentation $instrumentation;

    public static function register(): void
    {
        if (Sdk::isInstrumentationDisabled(self::NAME)) {
            return;
        }

        self::$instrumentation = new CachedInstrumentation(
            'uk.gov.opg.use-an-lpa.aws',
            null,
            'https://opentelemetry.io/schemas/1.38.0',
        );

        hook(AwsClient::class, '__call', pre: self::pre(...), post: self::post(...));
    }

    private static function pre(AwsClient $client, array $params): ?array
    {
        if (Suppression::isActive()) {
            return null;
        }

        $parentContext = Context::getCurrent();
        $serviceName   = $client->getApi()->getServiceName();
        $command       = (string) $params[0];
        $args          = $params[1][0] ?? [];

        $spanBuilder = self::$instrumentation->tracer()
            ->spanBuilder($serviceName ?: 'UnknownAWSService')
            ->setParent($parentContext)
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttributes([
                'rpc.method'  => $command,
                'rpc.service' => $serviceName,
                'rpc.system'  => 'aws-api',
                'aws.region'  => $client->getRegion(),
            ]);

        if ($client instanceof DynamoDbClient && isset($args['TableName'])) {
            $spanBuilder->setAttribute('aws.dynamodb.table_names', [$args['TableName']]);
        }

        $span = $spanBuilder->startSpan();

        // The SDK makes its HTTP calls via Guzzle; this span already represents that work.
        OwnedScope::attach(self::NAME, Suppression::apply($span->storeInContext($parentContext)), $client);

        if (
            $client instanceof EventBridgeClient
            && in_array(strtolower($command), ['putevents', 'puteventsasync'], true)
        ) {
            return self::propagateEventEntries($params);
        }

        return null;
    }

    private static function propagateEventEntries(array $params): ?array
    {
        $headers = [];
        (new Propagator())->inject($headers);

        if (!isset($headers['x-amzn-trace-id'])) {
            return null;
        }

        // The SDK accepts the operation payload as either a positional or named argument.
        $argument = array_key_exists('args', $params[1]) ? 'args' : 0;
        $entries  = $params[1][$argument]['Entries'] ?? null;
        if (!is_array($entries)) {
            return null;
        }

        foreach ($entries as $index => $entry) {
            if (is_array($entry)) {
                $params[1][$argument]['Entries'][$index]['TraceHeader'] = $headers['x-amzn-trace-id'];
            }
        }

        return [1 => $params[1]];
    }

    private static function post(AwsClient $client, array $params, mixed $result, ?Throwable $exception): void
    {
        $span = OwnedScope::detach(self::NAME, $client);
        if ($span === null) {
            return;
        }

        if ($exception !== null) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }

        if ($result instanceof ResultInterface && isset($result['@metadata']['statusCode'])) {
            $span->setAttribute(HttpAttributes::HTTP_RESPONSE_STATUS_CODE, $result['@metadata']['statusCode']);
        }

        $span->end();
    }
}
