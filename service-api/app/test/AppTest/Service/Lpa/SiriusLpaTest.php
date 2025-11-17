<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class SiriusLpaTest extends TestCase
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
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertInstanceOf(SiriusLpa::class, $sut);
    }

    #[Test]
    public function it_is_array_accessible(): void
    {
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertSame(700000000000, $sut['uId']);

        $sut['status'] = 'active';
        $this->assertSame('active', $sut['status']);

        unset($sut['status']);
        $this->assertArrayNotHasKey('status', $sut);
    }

    #[Test]
    public function it_can_be_iterated(): void
    {
        $sut = new SiriusLpa(
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
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertSame(
            [
                'uId' => 700000000000,
            ],
            $sut->toArray(),
        );
    }

    #[Test]
    public function it_becomes_the_expected_json(): void
    {
        $sut = new SiriusLpa(
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
        $sut = new SiriusLpa(
            [
                'uId'              => 700000000000,
                'status'           => 'Registered',
                'registrationDate' => '2019-08-31',
                'lpaIsCleansed'    => false,
            ],
            $this->loggerProphecy->reveal(),
        );

        $this->assertSame('700000000000', $sut->getUid());
        $this->assertSame('Registered', $sut->getStatus());
        $this->assertEquals(new DateTimeImmutable('2019-08-31'), $sut->getRegistrationDate());
        $this->assertFalse($sut->getLpaIsCleansed());
    }

    #[Test]
    public function it_implements_with_thing_functionality(): void
    {
        $sut = new SiriusLpa(
            [
                'uId'               => 700000000000,
                'status'            => 'Registered',
                'registrationDate'  => '2019-08-31',
                'lpaIsCleansed'     => false,
                'donor'             => [
                    'uId' => 700000000001,
                ],
                'attorneys'         => [
                    [
                        'uId'       => 700000000002,
                        'firstname' => 'Attorney1',
                    ],
                ],
                'trustCorporations' => [
                    [
                        'uId'       => 700000000003,
                        'firstname' => 'Trust Corporation1',
                    ],
                ],
            ],
            $this->loggerProphecy->reveal(),
        );

        $sut = $sut->withAttorneys(
            [
                new SiriusPerson(
                    [
                        'uId'       => 700000000004,
                        'firstname' => 'Attorney2',
                    ],
                    $this->loggerProphecy->reveal(),
                ),
            ]
        );

        $this->assertCount(1, $sut->getAttorneys());
        $this->assertSame('Attorney2', $sut->getAttorneys()[0]->getFirstnames());

        $sut = $sut->withTrustCorporations(
            [
                new SiriusPerson(
                    [
                        'uId'       => 700000000005,
                        'firstname' => 'Trust Corporation2',
                    ],
                    $this->loggerProphecy->reveal(),
                ),
            ]
        );

        $this->assertCount(1, $sut->getTrustCorporations());
        $this->assertSame('Trust Corporation2', $sut->getTrustCorporations()[0]->getFirstnames());
    }
}
