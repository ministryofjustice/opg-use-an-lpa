<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\SiriusLpa;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiriusLpaTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ]
        );

        $this->assertInstanceOf(SiriusLpa::class, $sut);
    }

    #[Test]
    public function it_is_array_accessible(): void
    {
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ]
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
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ]
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
            ]
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
        $sut = new SiriusLpa(
            [
                'uId' => 700000000000,
            ]
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
                'uId' => 700000000000,
                'systemStatus' => 'true',
                'status' => 'Registered',
            ]
        );

        $this->assertSame('700000000000', $sut->getUid());
        $this->assertSame('true', $sut->getSystemStatus());
        $this->assertSame('Registered', $sut->getStatus());
    }
}
