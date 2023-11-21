<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class GetAttorneyStatusTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function returns_0_if_attorney_is_active(): void
    {
        $attorney = ['id' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($attorney));
    }

    /** @test */
    public function returns_1_if_attorney_is_a_ghost(): void
    {
        $attorney = ['uId' => 7, 'firstname' => '', 'surname' => '', 'systemStatus' => true];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($attorney));
    }

    /** @test */
    public function returns_2_if_attorney_is_inactive(): void
    {
        $attorney = ['uId' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false];

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($attorney));
    }
}
