<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\Person;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;
use App\Service\Lpa\SiriusPerson;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function returns_0_if_attorney_is_active(): void
    {
        $attorney = new SiriusPerson(['id' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true]);

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::ACTIVE_ATTORNEY, ($status)($attorney));
    }

    #[Test]
    public function returns_0_if_attorney_is_active_combined_format(): void
    {
        $attorney = new LpaStoreAttorney(
            $addressLine1 = '81 NighOnTimeWeBuiltIt Street',
            $addressLine2 = null,
            $addressLine3 = null,
            $country      = 'GB',
            $county       = null,
            $dob          = new \DateTimeImmutable('1982-07-24'),
            $email        = null,
            $firstname    = 'Herman',
            $firstnames   = 'Herman',
            $name         = null,
            $otherNames   = null,
            $postcode     = null,
            $surname      = 'Seakrest',
            $systemStatus = 'active',
            $town         = 'Mahhhhhhhhhh',
            $type         = null,
            $uId          = '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d'
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::ACTIVE_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_1_if_attorney_is_a_ghost(): void
    {
        $attorney = new SiriusPerson(['uId' => 7, 'firstname' => '', 'surname' => '', 'systemStatus' => true]);

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::GHOST_ATTORNEY, ($status)($attorney));
    }

    #[Test]
    public function returns_2_if_attorney_is_inactive(): void
    {
        $attorney = new SiriusPerson(['uId' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false]);

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::INACTIVE_ATTORNEY, ($status)($attorney));
    }
}
