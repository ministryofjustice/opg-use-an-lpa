<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\CachedSiriusLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CachedSiriusLpasTest extends TestCase
{
    private SiriusLpas&MockObject $siriusLpas;

    public function setUp(): void
    {
        $this->siriusLpas = $this->createMock(SiriusLpas::class);
    }

    #[Test]
    public function it_should_cache_an_lpa_fetched_by_get(): void
    {
        $lpa = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

        $sut = new CachedSiriusLpas($this->siriusLpas, $this->createStub(LoggerInterface::class));

        $this->assertSame($lpa, $sut->get('700000000001'));
        $this->assertSame($lpa, $sut->get('700000000001'));
    }

    #[Test]
    public function it_should_not_cache_an_lpa_that_was_not_found(): void
    {
        $this->siriusLpas
            ->expects($this->exactly(2))
            ->method('get')
            ->with('700000000001')
            ->willReturn(null);

        $sut = new CachedSiriusLpas($this->siriusLpas, $this->createStub(LoggerInterface::class));

        $this->assertNull($sut->get('700000000001'));
        $this->assertNull($sut->get('700000000001'));
    }

    #[Test]
    public function it_should_only_lookup_lpas_that_are_not_cached(): void
    {
        $lpaOne   = $this->createStub(LpaInterface::class);
        $lpaTwo   = $this->createStub(LpaInterface::class);
        $lpaThree = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpaOne);

        $this->siriusLpas
            ->expects($this->exactly(2))
            ->method('lookup')
            ->willReturnMap(
                [
                    [
                        [
                            '700000000002',
                            '700000000003',
                            '700000000004',
                        ],
                        [
                            '700000000002' => $lpaTwo,
                            '700000000003' => $lpaThree,
                        ],
                    ],
                    [
                        ['700000000004'],
                        [],
                    ],
                ]
            );

        $sut = new CachedSiriusLpas($this->siriusLpas, $this->createStub(LoggerInterface::class));

        $sut->get('700000000001');

        $expected = [
            '700000000001' => $lpaOne,
            '700000000002' => $lpaTwo,
            '700000000003' => $lpaThree,
        ];

        $uids = [
            '700000000001',
            '700000000002',
            '700000000003',
            '700000000004',
        ];

        $this->assertSame($expected, $sut->lookup($uids));
        $this->assertSame($expected, $sut->lookup($uids));
        $this->assertSame($lpaTwo, $sut->get('700000000002'));
    }

    #[Test]
    public function it_should_not_call_sirius_when_all_lpas_are_cached(): void
    {
        $lpa = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

        $this->siriusLpas->expects($this->never())->method('lookup');

        $sut = new CachedSiriusLpas($this->siriusLpas, $this->createStub(LoggerInterface::class));

        $sut->get('700000000001');

        $this->assertSame(['700000000001' => $lpa], $sut->lookup(['700000000001']));
    }

    #[Test]
    public function it_should_log_cache_activity_at_debug_level(): void
    {
        $this->siriusLpas->method('get')->willReturn($this->createStub(LpaInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('debug');

        $sut = new CachedSiriusLpas($this->siriusLpas, $logger);
        $sut->get('700000000001');
    }
}
