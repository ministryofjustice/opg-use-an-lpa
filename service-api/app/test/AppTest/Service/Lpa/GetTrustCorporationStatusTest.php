<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreTrustCorporations;
use App\Entity\Sirius\SiriusLpaTrustCorporations;
use App\Service\Lpa\GetTrustCorporationStatus;
use App\Service\Lpa\SiriusPerson;
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

        $trustCorporation = new SiriusPerson(
            [
                'uId' => 7,
                'companyName' => 'ABC Ltd',
                'systemStatus' => true
            ]
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_0_if_trustCorporation_is_active_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporations(
            $addressLine1 = '103 Line 1',
            $addressLine2 = null,
            $addressLine3 = null,
            $countryName  = 'UK',
            $country      = 'GB',
            $county       = null,
            $dob          = null,
            $email        = null,
            $firstname    = null,
            $firstnames   = null,
            $name         = 'ABC Ltd',
            $otherNames   = null,
            $postcode     = null,
            $surname      = null,
            $systemStatus = 'active',
            $town         = 'Town',
            $type         = null,
            $uId          = '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(0, ($status)($trustCorporation));
    }
    #[Test]
    public function returns_1_if_trustCorporation_is_a_ghost(): void
    {
        $trustCorporation = new SiriusPerson(
            [
                'uId' => 8,
                'companyName' => '',
                'systemStatus' => false
            ]
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($trustCorporation));
    }
    #[Test]
    public function returns_1_if_trustCorporation_is_a_ghost_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporations(
            $addressLine1 = '103 Line 1',
            $addressLine2 = null,
            $addressLine3 = null,
            $companyName  = '',
            $country      = 'GB',
            $county       = null,
            $dob          = null,
            $email        = null,
            $firstname    = null,
            $firstnames   = null,
            $name         = '',
            $otherNames   = null,
            $postcode     = null,
            $surname      = null,
            $systemStatus = 'active',
            $town         = 'Town',
            $type         = null,
            $uId          = '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(1, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_2_if_trustCorporation_is_inactive(): void
    {
        $trustCorporation = new SiriusPerson(
            [
                'uId' => 7,
                'companyName' => 'XYZ Ltd',
                'systemStatus' => false
            ]
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
    #[Test]
    public function returns_2_if_trustCorporation_is_inactive_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporations(
            $addressLine1 = '103 Line 1',
            $addressLine2 = null,
            $addressLine3 = null,
            $country      = 'GB',
            $county       = null,
            $companyName  = 'XYZ Ltd',
            $dob          = null,
            $email        = null,
            $firstname    = null,
            $firstnames   = null,
            $name         = '',
            $otherNames   = null,
            $postcode     = null,
            $surname      = null,
            $systemStatus = 'false',
            $town         = 'Town',
            $type         = null,
            $uId          = '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
    #[Test]
    public function returns_2_if_trustCorporation_is_inactive_combined_format_sirius(): void
    {
        $trustCorporation = new SiriusLpaTrustCorporations(
            $addressLine1 = 'Street 1',
            $addressLine2 = 'Street 2',
            $addressLine3 = 'Street 3',
            $country      = 'GB',
            $county       = 'County',
            $dob          = null,
            $email        = null,
            $firstname    = 'trust',
            $firstnames   = null,
            $name         = null,
            $otherNames   = null,
            $postcode     = 'ABC 123',
            $surname      = 'test',
            $systemStatus = 'false',
            $town         = 'Town',
            $type         = 'Primary',
            $uId          = '7000-0015-1998',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
}
