<?php

declare(strict_types=1);

namespace Telemetry;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextKeyInterface;

/**
 * Keeps the original incoming X-Ray header scoped to the request for access-log correlation.
 */
final class IncomingTraceHeader
{
    public static function key(): ContextKeyInterface
    {
        static $key;

        return $key ??= Context::createKey(self::class);
    }
}
