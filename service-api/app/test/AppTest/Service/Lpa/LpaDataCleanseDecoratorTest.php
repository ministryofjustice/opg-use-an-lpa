<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Service\Lpa\LpaDataCleanseDecorator;
use Common\Entity\Lpa;
use PHPUnit\Framework\TestCase;

class LpaDataCleanseDecoratorTest extends TestCase
{
    /** @test */
    public function it_decorates_a_lpa_object() {
        $lpaResponseProphecy = $this->prophesize(LpaInterface::class);
        $lpaResponseProphecy->getData()
            ->willReturn([
                'id' => '12345',
                'attorneys' => []
            ]);

        $decoratedLpaInterface = new LpaDataCleanseDecorator($lpaResponseProphecy->reveal());

        $this->assertInstanceOf(LpaInterface::class, $decoratedLpaInterface);
        $this->assertNotEquals($lpaResponseProphecy->reveal(), $decoratedLpaInterface);
        $this->assertArrayHasKey('id', $decoratedLpaInterface->getData());
    }

    /** @test */
    public function it_removes_ghost_attorneys() {
        $lpaResponseProphecy = $this->prophesize(LpaInterface::class);
        $lpaResponseProphecy->getData()
            ->willReturn([
                'id' => '12345',
                'attorneys' => [
                    [
                        'id' => 0,
                        'firstname' => '',
                        'surname' => ''
                    ],
                    [
                        'id' => 1,
                        'firstname' => 'Test',
                        'surname' => 'Testerson'
                    ]
                ]
            ]);

        $decoratedLpaInterface = new LpaDataCleanseDecorator($lpaResponseProphecy->reveal());
        $lpa = $decoratedLpaInterface->getData();

        $this->assertCount(1, $lpa['attorneys']);
    }

    /** @test */
    public function it_can_cope_with_no_attorneys() {
        $lpaResponseProphecy = $this->prophesize(LpaInterface::class);
        $lpaResponseProphecy->getData()
            ->willReturn([
                'id' => '12345',
                'attorneys' => []
            ]);

        $decoratedLpaInterface = new LpaDataCleanseDecorator($lpaResponseProphecy->reveal());
        $lpa = $decoratedLpaInterface->getData();

        $this->assertCount(0, $lpa['attorneys']);
    }

    /** @test */
    public function it_wont_alter_valid_data() {
        $lpaResponseProphecy = $this->prophesize(LpaInterface::class);
        $lpaResponseProphecy->getData()
            ->willReturn([
                'id' => '12345',
                'attorneys' => [
                    [
                        'id' => 0,
                        'firstname' => 'Test',
                        'surname' => 'Testerson'
                    ]
                ]
            ]);

        $decoratedLpaInterface = new LpaDataCleanseDecorator($lpaResponseProphecy->reveal());
        $lpa = $decoratedLpaInterface->getData();

        $this->assertCount(1, $lpa['attorneys']);
    }
}
