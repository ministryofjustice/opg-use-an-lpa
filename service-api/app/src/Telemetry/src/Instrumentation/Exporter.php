<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use Telemetry\Suppression;

use function OpenTelemetry\Instrumentation\hook;

/**
 * Prevents the telemetry exporters' own HTTP calls (made via Guzzle) from being traced.
 */
class Exporter
{
    public const NAME = 'exporter';

    public static function register(): void
    {
        hook(TransportInterface::class, 'send', pre: self::pre(...), post: self::post(...));
    }

    private static function pre(TransportInterface $transport): void
    {
        OwnedScope::attach(self::NAME, Suppression::apply(Context::getCurrent()), $transport);
    }

    private static function post(TransportInterface $transport): void
    {
        OwnedScope::detach(self::NAME, $transport);
    }
}
