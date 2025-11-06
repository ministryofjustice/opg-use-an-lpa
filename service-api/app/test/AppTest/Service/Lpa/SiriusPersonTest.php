<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\SiriusPerson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class SiriusPersonTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    protected function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new SiriusPerson(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertInstanceOf(SiriusPerson::class, $sut);
    }

    #[Test]
    public function it_is_array_accessible(): void
    {
        $sut = new SiriusPerson(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertEquals(700000000000, $sut['uId']);

        $sut['status'] = 'active';
        $this->assertEquals('active', $sut['status']);

        unset($sut['status']);
        $this->assertFalse(isset($sut['status']));
    }

    #[Test]
    public function it_can_be_iterated(): void
    {
        $sut = new SiriusPerson(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        foreach ($sut as $key => $value) {
            $this->assertEquals($value, $sut[$key]);
        }
    }

    #[Test]
    public function it_can_become_an_array(): void
    {
        $sut = new SiriusPerson(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertEquals(
            [
                'uId' => 700000000000,
            ],
            $sut->toArray(),
        );
    }

    #[Test]
    public function it_becomes_the_expected_json(): void
    {
        $sut = new SiriusPerson(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertJsonStringEqualsJsonString(
            '{"uId":700000000000}',
            json_encode($sut),
        );
    }

    #[Test]
    public function it_typecasts_on_getters(): void
    {
        $sut = new SiriusPerson(
            [
                'uId'          => 700000000000,
                'systemStatus' => 1,
                'companyName'  => null,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertSame('700000000000', $sut->getUid());
        $this->assertSame(true, $sut->getStatus());
        $this->assertSame('', $sut->getCompanyName());
    }
}
