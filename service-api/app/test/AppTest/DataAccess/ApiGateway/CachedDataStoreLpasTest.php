<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\CachedDataStoreLpas;
use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CachedDataStoreLpasTest extends TestCase
{
    private DataStoreLpas&MockObject $dataStoreLpas;

    public function setUp(): void
    {
        $this->dataStoreLpas = $this->createMock(DataStoreLpas::class);
    }

    #[Test]
    public function it_should_pass_the_originator_id_to_the_data_store_and_return_itself(): void
    {
        $this->dataStoreLpas
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with('originator')
            ->willReturnSelf();

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));

        $this->assertSame($sut, $sut->setOriginatorId('originator'));
    }

    #[Test]
    public function it_should_cache_an_lpa_fetched_by_get(): void
    {
        $lpa = $this->createLpa('M-0000-0000-0001');

        $this->dataStoreLpas
            ->expects($this->once())
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturn($lpa);

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));

        $this->assertSame($lpa, $sut->setOriginatorId('originator')->get('M-0000-0000-0001'));
        $this->assertSame($lpa, $sut->setOriginatorId('originator')->get('M-0000-0000-0001'));
    }

    #[Test]
    public function it_should_not_cache_an_lpa_that_was_not_found(): void
    {
        $this->dataStoreLpas
            ->expects($this->exactly(2))
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturn(null);

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));
        $sut->setOriginatorId('originator');

        $this->assertNull($sut->get('M-0000-0000-0001'));
        $this->assertNull($sut->get('M-0000-0000-0001'));
    }

    #[Test]
    public function it_should_fetch_again_for_a_different_originator_id(): void
    {
        $lpaOne = $this->createLpa('M-0000-0000-0001');
        $lpaTwo = $this->createLpa('M-0000-0000-0001');

        $this->dataStoreLpas
            ->expects($this->exactly(2))
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturnOnConsecutiveCalls($lpaOne, $lpaTwo);

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));

        $this->assertSame($lpaOne, $sut->setOriginatorId('originator-one')->get('M-0000-0000-0001'));
        $this->assertSame($lpaTwo, $sut->setOriginatorId('originator-two')->get('M-0000-0000-0001'));
        $this->assertSame($lpaOne, $sut->setOriginatorId('originator-one')->get('M-0000-0000-0001'));
    }

    #[Test]
    public function it_should_not_cache_when_no_originator_id_is_set(): void
    {
        $lpa = $this->createLpa('M-0000-0000-0001');

        $this->dataStoreLpas
            ->expects($this->exactly(2))
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturn($lpa);

        $this->dataStoreLpas
            ->expects($this->exactly(2))
            ->method('lookup')
            ->with(['M-0000-0000-0001'])
            ->willReturn([$lpa]);

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));

        $sut->get('M-0000-0000-0001');
        $sut->get('M-0000-0000-0001');
        $sut->lookup(['M-0000-0000-0001']);
        $sut->lookup(['M-0000-0000-0001']);
    }

    #[Test]
    public function it_should_only_lookup_lpas_that_are_not_cached(): void
    {
        $lpaOne   = $this->createLpa('M-0000-0000-0001');
        $lpaTwo   = $this->createLpa('M-0000-0000-0002');
        $lpaThree = $this->createLpa('M-0000-0000-0003');

        $this->dataStoreLpas
            ->expects($this->once())
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturn($lpaOne);

        $this->dataStoreLpas
            ->expects($this->exactly(2))
            ->method('lookup')
            ->willReturnMap(
                [
                    [
                        [
                            'M-0000-0000-0002',
                            'M-0000-0000-0003',
                            'M-0000-0000-0004',
                        ],
                        [
                            $lpaTwo,
                            $lpaThree,
                        ],
                    ],
                    [
                        ['M-0000-0000-0004'],
                        [],
                    ],
                ]
            );

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));
        $sut->setOriginatorId('originator');

        $sut->get('M-0000-0000-0001');

        $uids = [
            'M-0000-0000-0001',
            'M-0000-0000-0002',
            'M-0000-0000-0003',
            'M-0000-0000-0004',
        ];

        $this->assertSame([$lpaOne, $lpaTwo, $lpaThree], $sut->lookup($uids));
        $this->assertSame([$lpaOne, $lpaTwo, $lpaThree], $sut->lookup($uids));
        $this->assertSame($lpaTwo, $sut->get('M-0000-0000-0002'));
    }

    #[Test]
    public function it_should_not_call_the_data_store_when_all_lpas_are_cached(): void
    {
        $lpa = $this->createLpa('M-0000-0000-0001');

        $this->dataStoreLpas
            ->expects($this->once())
            ->method('get')
            ->with('M-0000-0000-0001')
            ->willReturn($lpa);

        $this->dataStoreLpas->expects($this->never())->method('lookup');

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $this->createStub(LoggerInterface::class));
        $sut->setOriginatorId('originator');

        $sut->get('M-0000-0000-0001');

        $this->assertSame([$lpa], $sut->lookup(['M-0000-0000-0001']));
    }

    #[Test]
    public function it_should_log_cache_activity_at_debug_level(): void
    {
        $this->dataStoreLpas->method('get')->willReturn($this->createLpa('M-0000-0000-0001'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('debug');

        $sut = new CachedDataStoreLpas($this->dataStoreLpas, $logger);
        $sut->setOriginatorId('originator');
        $sut->get('M-0000-0000-0001');
    }

    private function createLpa(string $uid): LpaInterface
    {
        $data = $this->createStub(Lpa::class);
        $data->method('getUid')->willReturn($uid);

        $lpa = $this->createStub(LpaInterface::class);
        $lpa->method('getData')->willReturn($data);

        return $lpa;
    }
}
