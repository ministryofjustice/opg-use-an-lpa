<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
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
        $trustCorporation = new LpaStoreTrustCorporation(
            addressLine1: '103 Line 1',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          null,
            email:        null,
            firstnames:   null,
            name:         'ABC Ltd',
            postcode:     null,
            surname:      null,
            systemStatus: 'active',
            town:         'Town',
            type:         null,
            uId:          '1d95993a-ffbb-484c-b2fe-f4cca51801da',
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
                'uId'          => 8,
                'companyName'  => '',
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
        $trustCorporation = new LpaStoreTrustCorporation(
            addressLine1: '103 Line 1',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          null,
            email:        null,
            firstnames:   null,
            name:         '',
            postcode:     null,
            surname:      null,
            systemStatus: 'active',
            town:         'Town',
            type:         null,
            uId:          '1d95993a-ffbb-484c-b2fe-f4cca51801da',
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
        $trustCorporation = new LpaStoreTrustCorporation(
            addressLine1: '103 Line 1',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          null,
            email:        null,
            firstnames:   null,
            name:         'XYZ Ltd',
            postcode:     null,
            surname:      null,
            systemStatus: 'false',
            town:         'Town',
            type:         null,
            uId:          '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
    #[Test]
    public function returns_2_if_trustCorporation_is_inactive_combined_format_sirius(): void
    {
        $trustCorporation = new SiriusLpaTrustCorporation(
            addressLine1: 'Street 1',
            addressLine2: 'Street 2',
            addressLine3: 'Street 3',
            companyName:  'XYZ Ltd',
            country:      'GB',
            county:       'County',
            dob:          null,
            email:        null,
            firstname:    'trust',
            id:           '998',
            middlenames:  null,
            otherNames:   null,
            postcode:     'ABC 123',
            surname:      'test',
            systemStatus: 'false',
            town:         'Town',
            type:         'Primary',
            uId:          '700000151998',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(2, ($status)($trustCorporation));
    }
}
