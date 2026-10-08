<?php

declare(strict_types=1);

namespace App\Service\Log;

use Blazon\PSR11MonoLog\FactoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\IgnoreClassForCodeCoverage;

/**
 * @codeCoverageIgnore
 */
final class OpgJsonFormatterFactory implements FactoryInterface
{
    public function __invoke(array $options): OpgJsonFormatter
    {
        if (!isset($options['serviceName']) || !is_string($options['serviceName']) || $options['serviceName'] === '') {
            throw new InvalidArgumentException('A serviceName is required for OPG JSON logging.');
        }

        return new OpgJsonFormatter($options['serviceName']);
    }
}
