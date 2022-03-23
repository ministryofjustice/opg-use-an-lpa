<?php

namespace AppTest\Service\Lpa;

use App\Service\Lpa\GetTrustCorporationStatus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetTrustCorporationStatusTest extends TestCase
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
    public function returns_0_if_trustCorporation_is_active()
    {
        $trustCorporation = ['id' => 7, 'companyName' => 'ABC Ltd' , 'systemStatus' => true];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($trustCorporation));
    }

    /** @test */
    public function returns_1_if_trustCorporation_is_a_ghost()
    {
        $trustCorporation = ['id' => 8, 'companyName' => '', 'systemStatus' => false];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($trustCorporation));
    }

    /** @test */
    public function returns_2_if_trustCorporation_is_inactive()
    {
        $trustCorporation = ['id' => 7, 'companyName' => 'XYZ Ltd', 'systemStatus' => false];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
}
