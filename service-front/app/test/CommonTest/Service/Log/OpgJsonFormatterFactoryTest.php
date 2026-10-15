<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\OpgJsonFormatter;
use Common\Service\Log\OpgJsonFormatterFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpgJsonFormatterFactory::class)]
final class OpgJsonFormatterFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('serviceNames')]
    public function it_should_create_the_formatter_from_the_supplied_options(string $serviceName): void
    {
        $options = ['serviceName' => $serviceName];

        self::assertEquals(new OpgJsonFormatter($serviceName), (new OpgJsonFormatterFactory())($options));
    }

    public static function serviceNames(): array
    {
        return [
            'actor'  => ['actor-app'],
            'viewer' => ['viewer-app'],
        ];
    }

    #[Test]
    #[DataProvider('invalidOptions')]
    public function it_should_reject_an_invalid_service_name(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A serviceName is required for OPG JSON logging.');

        (new OpgJsonFormatterFactory())($options);
    }

    public static function invalidOptions(): array
    {
        return [
            'missing'    => [[]],
            'null'       => [['serviceName' => null]],
            'non-string' => [['serviceName' => 123]],
            'empty'      => [['serviceName' => '']],
        ];
    }
}
