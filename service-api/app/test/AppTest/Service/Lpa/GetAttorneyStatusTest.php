<?php

namespace AppTest\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetAttorneyStatusTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function returns_0_if_attorney_is_active()
    {
        $attorney = ['id' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($attorney));
    }

    /** @test */
    public function returns_1_if_attorney_is_a_ghost()
    {
        $attorney = ['id' => 7, 'firstname' => '', 'surname' => '', 'systemStatus' => true];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($attorney));
    }

    /** @test */
    public function returns_2_if_attorney_is_inactive()
    {
        $attorney = ['id' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($attorney));
    }
}
