<?php

declare(strict_types=1);

namespace Telemetry;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;

/**
 * Allows instrumentation to be switched off for a block of work, e.g. HTTP calls made by the AWS SDK
 * (already traced as AWS spans) or by the resource detectors whilst the SDK itself is initialising.
 */
final class Suppression
{
    public static function key(): ContextKeyInterface
    {
        static $key;

        return $key ??= Context::createKey(self::class);
    }

    public static function isActive(): bool
    {
        return Context::getCurrent()->get(self::key()) === true;
    }

    public static function apply(ContextInterface $context): ContextInterface
    {
        return $context->with(self::key(), true);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $scope = self::apply(Context::getCurrent())->activate();

        try {
            return $callback();
        } finally {
            $scope->detach();
        }
    }
}
