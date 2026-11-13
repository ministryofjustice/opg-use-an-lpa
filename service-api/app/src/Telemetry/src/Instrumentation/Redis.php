<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Sdk;
use Redis as RedisClient;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AbstractTagAwareAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Telemetry\Suppression;
use Throwable;
use WeakMap;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Times Redis adapter work, not cache callbacks or deferred-write queueing.
 */
final class Redis
{
    public const string NAME = 'redis';

    private static CachedInstrumentation $instrumentation;

    /** @var WeakMap<object, array<non-empty-string, string|int>> */
    private static WeakMap $endpoints;

    public static function register(): void
    {
        if (Sdk::isInstrumentationDisabled(self::NAME)) {
            return;
        }

        self::$instrumentation = new CachedInstrumentation(
            'uk.gov.opg.use-an-lpa.redis',
            null,
            'https://opentelemetry.io/schemas/1.38.0',
        );
        self::$endpoints       = new WeakMap();

        foreach ([RedisAdapter::class, RedisTagAwareAdapter::class] as $class) {
            hook($class, '__construct', post: self::captureEndpoint(...));

            foreach (
                [
                    'doFetch' => 'MGET',
                    'doSave'  => 'SET',
                    'doHave'  => 'EXISTS',
                    'doClear' => 'CLEAR',
                ] as $method => $operation
            ) {
                self::registerOperation($class, $method, $operation);
            }
        }

        self::registerOperation(RedisTagAwareAdapter::class, 'doInvalidate', 'INVALIDATE');
        // Deleting tagged items consumes a generator; hook its caller so execution is included.
        self::registerOperation(AbstractAdapter::class, 'deleteItems', 'DEL');
        self::registerOperation(AbstractTagAwareAdapter::class, 'deleteItems', 'DEL');
    }

    private static function captureEndpoint(object $adapter, array $params, mixed $result, ?Throwable $exception): void
    {
        $redis = $params[0] ?? null;
        if ($exception !== null || !$redis instanceof RedisClient) {
            return;
        }

        self::$endpoints[$adapter] = [
            'server.address' => $redis->getHost(),
            'server.port'    => $redis->getPort(),
        ];
    }

    private static function registerOperation(string $class, string $method, string $operation): void
    {
        hook(
            $class,
            $method,
            pre: static fn (object $adapter, array $params) => self::pre($adapter, $params, $method, $operation),
            post: static fn (object $adapter, array $params, mixed $result, ?Throwable $exception) => self::post(
                $adapter,
                $result,
                $exception,
                $method,
            ),
        );
    }

    private static function pre(object $adapter, array $params, string $method, string $operation): void
    {
        if (!$adapter instanceof RedisAdapter && !$adapter instanceof RedisTagAwareAdapter) {
            return;
        }

        if (Suppression::isActive() || ($params[0] ?? null) === []) {
            return;
        }

        $parent = Context::getCurrent();
        $span   = self::$instrumentation->tracer()
            ->spanBuilder('Redis ' . $operation)
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttributes([
                DbAttributes::DB_SYSTEM_NAME    => 'redis',
                DbAttributes::DB_OPERATION_NAME => $operation,
            ])
            ->startSpan();

        $endpoint = self::$endpoints[$adapter] ?? null;
        if ($endpoint !== null) {
            $span->setAttributes($endpoint);
        }

        OwnedScope::attach(self::NAME . '.' . $method, Suppression::apply($span->storeInContext($parent)), $adapter);
    }

    private static function post(object $adapter, mixed $result, ?Throwable $exception, string $method): void
    {
        $span = OwnedScope::detach(self::NAME . '.' . $method, $adapter);
        if ($span === null) {
            return;
        }

        $failed = match ($method) {
            'doHave' => false,
            'doSave' => $result !== [] && $result !== true,
            default => $result === false,
        };

        if ($exception !== null || $failed) {
            $span->setStatus(StatusCode::STATUS_ERROR);
            $span->setAttribute('error.type', $exception !== null ? $exception::class : 'cache.operation.failed');

            // Exception messages and stack traces can contain cache keys or cached data.
            if ($exception !== null) {
                $span->addEvent('exception', ['exception.type' => $exception::class]);
            }
        }

        $span->end();
    }
}
