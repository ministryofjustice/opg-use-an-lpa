<?php

declare(strict_types=1);

namespace Telemetry;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextKeyInterface;
use OpenTelemetry\SDK\Trace\IdGeneratorInterface;

/**
 * An ALB Root identifies a trace, but is not a remote parent span.
 * Preserve it for parentless server spans without inventing upstream ancestry or a sampling decision.
 */
final class AlbRootIdGenerator implements IdGeneratorInterface
{
    public function __construct(private readonly IdGeneratorInterface $fallback)
    {
    }

    public static function key(): ContextKeyInterface
    {
        static $key;

        return $key ??= Context::createKey(self::class);
    }

    public function generateTraceId(): string
    {
        $header = Context::getCurrent()->get(self::key());
        if (
            is_string($header)
            && preg_match('/(?:^|;)\s*(?:Parent|Sampled)=/', $header) !== 1
            && preg_match('/(?:^|;)\s*Root=1-([0-9a-f]{8})-([0-9a-f]{24})(?:;|$)/', $header, $matches) === 1
        ) {
            $traceId = $matches[1] . $matches[2];
            if ($traceId !== str_repeat('0', 32)) {
                return $traceId;
            }
        }

        return $this->fallback->generateTraceId();
    }

    public function generateSpanId(): string
    {
        return $this->fallback->generateSpanId();
    }
}
