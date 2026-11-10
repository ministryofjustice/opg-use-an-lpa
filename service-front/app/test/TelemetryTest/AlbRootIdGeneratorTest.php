<?php

declare(strict_types=1);

namespace TelemetryTest;

use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\IdGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Telemetry\AlbRootIdGenerator;

#[CoversClass(AlbRootIdGenerator::class)]
final class AlbRootIdGeneratorTest extends TestCase
{
    #[Test]
    #[DataProvider('headers')]
    public function it_should_reuse_only_valid_root_only_headers(?string $header, bool $reuse): void
    {
        $fallback = $this->createMock(IdGeneratorInterface::class);
        $fallback->expects($reuse ? self::never() : self::once())
            ->method('generateTraceId')
            ->willReturn(str_repeat('b', 32));
        $generator = new AlbRootIdGenerator($fallback);
        $scope     = Context::getCurrent()->with(AlbRootIdGenerator::key(), $header)->activate();

        try {
            self::assertSame(
                $reuse ? '67891233abcdef012345678912345678' : str_repeat('b', 32),
                $generator->generateTraceId(),
            );
        } finally {
            $scope->detach();
        }

        self::assertNull(Context::getCurrent()->get(AlbRootIdGenerator::key()));
    }

    public static function headers(): array
    {
        $root = 'Root=1-67891233-abcdef012345678912345678';

        return [
            'root only'        => [
                $root,
                true,
            ],
            'ALB self field'   => [
                'Self=1-67891233-111111111111111111111111;' . $root,
                true,
            ],
            'custom fields'    => [
                $root . ';CalledFrom=app',
                true,
            ],
            'missing'          => [
                null,
                false,
            ],
            'empty'            => [
                '',
                false,
            ],
            'invalid hex'      => [
                'Root=1-67891233-abcdef01234567891234567g',
                false,
            ],
            'invalid length'   => [
                $root . '0',
                false,
            ],
            'invalid version'  => [
                'Root=2-67891233-abcdef012345678912345678',
                false,
            ],
            'zero'             => [
                'Root=1-00000000-000000000000000000000000',
                false,
            ],
            'complete context' => [
                $root . ';Parent=53995c3f42cd8ad8;Sampled=1',
                false,
            ],
            'partial parent'   => [
                $root . ';Parent=53995c3f42cd8ad8',
                false,
            ],
            'partial sampling' => [
                $root . ';Sampled=0',
                false,
            ],
        ];
    }

    #[Test]
    public function it_should_delegate_span_id_generation(): void
    {
        $fallback = $this->createMock(IdGeneratorInterface::class);
        $fallback->expects(self::once())->method('generateSpanId')->willReturn('53995c3f42cd8ad8');

        self::assertSame('53995c3f42cd8ad8', (new AlbRootIdGenerator($fallback))->generateSpanId());
    }
}
