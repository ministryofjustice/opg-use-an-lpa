<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\CachedSiriusLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CachedSiriusLpasTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|SiriusLpas $siriusLpas;

    public function setUp(): void
    {
        $this->siriusLpas = $this->prophesize(SiriusLpas::class);
    }

    #[Test]
    public function it_should_cache_an_lpa_fetched_by_get(): void
    {
        $lpa = $this->prophesize(LpaInterface::class)->reveal();

        $this->siriusLpas->get('700000000001')->shouldBeCalledOnce()->willReturn($lpa);

        $sut = new CachedSiriusLpas($this->siriusLpas->reveal());

        $this->assertSame($lpa, $sut->get('700000000001'));
        $this->assertSame($lpa, $sut->get('700000000001'));
    }

    #[Test]
    public function it_should_not_cache_an_lpa_that_was_not_found(): void
    {
        $this->siriusLpas->get('700000000001')->shouldBeCalledTimes(2)->willReturn(null);

        $sut = new CachedSiriusLpas($this->siriusLpas->reveal());

        $this->assertNull($sut->get('700000000001'));
        $this->assertNull($sut->get('700000000001'));
    }

    #[Test]
    public function it_should_only_lookup_lpas_that_are_not_cached(): void
    {
        $lpaOne   = $this->prophesize(LpaInterface::class)->reveal();
        $lpaTwo   = $this->prophesize(LpaInterface::class)->reveal();
        $lpaThree = $this->prophesize(LpaInterface::class)->reveal();

        $this->siriusLpas->get('700000000001')->shouldBeCalledOnce()->willReturn($lpaOne);
        $this->siriusLpas
            ->lookup(['700000000002', '700000000003', '700000000004'])
            ->shouldBeCalledOnce()
            ->willReturn(['700000000002' => $lpaTwo, '700000000003' => $lpaThree]);
        $this->siriusLpas->lookup(['700000000004'])->shouldBeCalledOnce()->willReturn([]);

        $sut = new CachedSiriusLpas($this->siriusLpas->reveal());

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
        $lpa = $this->prophesize(LpaInterface::class)->reveal();

        $this->siriusLpas->get('700000000001')->shouldBeCalledOnce()->willReturn($lpa);
        $this->siriusLpas->lookup(Argument::any())->shouldNotBeCalled();

        $sut = new CachedSiriusLpas($this->siriusLpas->reveal());

        $sut->get('700000000001');

        $this->assertSame(['700000000001' => $lpa], $sut->lookup(['700000000001']));
    }
}
