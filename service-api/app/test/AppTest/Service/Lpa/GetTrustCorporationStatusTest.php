<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
use App\Enum\ActorStatus;
use App\Service\Lpa\GetTrustCorporationStatus;
use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatus;
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

    protected function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    public function returns_active_if_trustCorporation_is_active(): void
    {
        $trustCorporation = new SiriusPerson(
            [
                'uId'          => 7,
                'companyName'  => 'ABC Ltd',
                'systemStatus' => true,
            ],
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::ACTIVE_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_active_if_trustCorporation_is_active_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporation(
            line1:       '103 Line 1',
            line2:       null,
            line3:       null,
            country:     'GB',
            county:      null,
            dateOfBirth: null,
            email:       null,
            firstNames:  null,
            name:        'ABC Ltd',
            postcode:    null,
            lastName:    null,
            status:      ActorStatus::ACTIVE,
            town:        'Town',
            uId:         '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::ACTIVE_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_ghost_if_trustCorporation_is_a_ghost(): void
    {
        $trustCorporation = new SiriusPerson(
            [
                'uId'          => 8,
                'companyName'  => '',
                'systemStatus' => false,
            ],
            $this->loggerProphecy->reveal()
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );


        $this->assertSame(TrustCorporationStatus::GHOST_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_ghost_if_trustCorporation_is_a_ghost_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporation(
            line1:       '103 Line 1',
            line2:       null,
            line3:       null,
            country:     'GB',
            county:      null,
            dateOfBirth: null,
            email:       null,
            firstNames:  null,
            name:        '',
            postcode:    null,
            lastName:    null,
            status:      ActorStatus::ACTIVE,
            town:        'Town',
            uId:         '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::GHOST_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_inactive_if_trustCorporation_is_inactive(): void
    {
        $trustCorporation = new SiriusPerson(
            [
                'uId'          => 7,
                'companyName'  => 'XYZ Ltd',
                'systemStatus' => false,
            ],
            $this->loggerProphecy->reveal(),
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::INACTIVE_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_inactive_if_trustCorporation_is_inactive_combined_format_lpastore(): void
    {
        $trustCorporation = new LpaStoreTrustCorporation(
            line1:       '103 Line 1',
            line2:       null,
            line3:       null,
            country:     'GB',
            county:      null,
            dateOfBirth: null,
            email:       null,
            firstNames:  null,
            name:        'XYZ Ltd',
            postcode:    null,
            lastName:    null,
            status:      ActorStatus::INACTIVE,
            town:        'Town',
            uId:         '1d95993a-ffbb-484c-b2fe-f4cca51801da',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::INACTIVE_TC, ($status)($trustCorporation));
    }

    #[Test]
    public function returns_inactive_if_trustCorporation_is_inactive_combined_format_sirius(): void
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
            systemStatus: ActorStatus::INACTIVE,
            town:         'Town',
            uId:          '700000151998',
        );

        $status = new GetTrustCorporationStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertSame(TrustCorporationStatus::INACTIVE_TC, ($status)($trustCorporation));
    }
}
