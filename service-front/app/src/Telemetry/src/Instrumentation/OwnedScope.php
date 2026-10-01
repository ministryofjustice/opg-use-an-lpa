<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;

/**
 * Tags an attached context with the hook and object it was created for so that the matching post hook only
 * ever detaches the scope that its own pre hook attached. Pre hooks may decide not to create a span, and a
 * single object may be hooked more than once (e.g. a MiddlewarePipe is both a handler and a middleware).
 */
final class OwnedScope
{
    private static function key(string $hook): ContextKeyInterface
    {
        static $keys = [];

        return $keys[$hook] ??= Context::createKey(self::class . '::' . $hook);
    }

    public static function attach(string $hook, ContextInterface $context, object $owner): void
    {
        Context::storage()->attach($context->with(self::key($hook), $owner));
    }

    public static function detach(string $hook, object $owner): ?SpanInterface
    {
        $scope = Context::storage()->scope();
        if ($scope === null || $scope->context()->get(self::key($hook)) !== $owner) {
            return null;
        }

        $scope->detach();

        return Span::fromContext($scope->context());
    }
}
