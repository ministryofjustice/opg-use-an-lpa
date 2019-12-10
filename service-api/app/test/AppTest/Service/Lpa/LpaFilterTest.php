<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Service\Lpa\LpaFilter;
use PHPUnit\Framework\TestCase;

class LpaFilterTest extends TestCase
{
    /** @test */
    public function it_decorates_a_lpa_object() {
        $lpaResponseProphecy = $this->prophesize(LpaInterface::class);
        $lpaResponseProphecy->getData()
            ->willReturn([
                'id' => '12345',
                'attorneys' => []
            ]);

        $decoratedLpaInterface = new LpaFilter($lpaResponseProphecy->reveal());

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
                    ]
                ]
            ]);

        $decoratedLpaInterface = new LpaFilter($lpaResponseProphecy->reveal());
        $lpa = $decoratedLpaInterface->getData();

        $this->assertCount(0, $lpa['attorneys']);
    }
}
