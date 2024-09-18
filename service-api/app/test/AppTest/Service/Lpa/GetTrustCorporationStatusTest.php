<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\GetTrustCorporationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class GetTrustCorporationStatusTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    public function returns_0_if_trustCorporation_is_active(): void
    {
        $trustCorporation = ['uId' => 7, 'companyName' => 'ABC Ltd', 'systemStatus' => true];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_1_if_trustCorporation_is_a_ghost(): void
    {
        $trustCorporation = ['uId' => 8, 'companyName' => '', 'systemStatus' => false];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_2_if_trustCorporation_is_inactive(): void
    {
        $trustCorporation = ['uId' => 7, 'companyName' => 'XYZ Ltd', 'systemStatus' => false];

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
}
