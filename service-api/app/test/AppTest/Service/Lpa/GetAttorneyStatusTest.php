<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;
use App\Service\Lpa\SiriusPerson;
use DateTimeImmutable;
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
        $attorney = new SiriusPerson(
            ['id' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
            $this->loggerProphecy->reveal(),
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::ACTIVE_ATTORNEY, ($status)($attorney));
    }

    #[Test]
    public function returns_0_if_attorney_is_active_combined_format_lpastore(): void
    {
        $attorney = new LpaStoreAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstnames:   'Herman',
            name:         null,
            postcode:     null,
            surname:      'Seakrest',
            systemStatus: 'active',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::ACTIVE_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_0_if_attorney_is_active_combined_format_sirius(): void
    {
        $attorney = new SiriusLpaAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstname:    'Herman',
            id:           '12345678',
            middlenames:  null,
            otherNames:   null,
            postcode:     null,
            surname:      'Seakrest',
            systemStatus: 'active',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '712345678',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::ACTIVE_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_1_if_attorney_is_a_ghost(): void
    {
        $attorney = new SiriusPerson(
            ['uId' => 7, 'firstname' => '', 'surname' => '', 'systemStatus' => true],
            $this->loggerProphecy->reveal(),
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::GHOST_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_1_if_attorney_is_a_ghost_combined_format_lpastore(): void
    {
        $attorney = new LpaStoreAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstnames:   '',
            name:         null,
            postcode:     null,
            surname:      '',
            systemStatus: 'true',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::GHOST_ATTORNEY, ($status)($attorney));
    }

    #[Test]
    public function returns_1_if_attorney_is_a_ghost_combined_format_sirius(): void
    {
        $attorney = new SiriusLpaAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstname:    '',
            id:           '77',
            middlenames:  null,
            otherNames:   null,
            postcode:     null,
            surname:      '',
            systemStatus: 'true',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '7',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::GHOST_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_2_if_attorney_is_inactive(): void
    {
        $attorney = new SiriusPerson(
            ['uId' => 7, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
            $this->loggerProphecy->reveal(),
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::INACTIVE_ATTORNEY, ($status)($attorney));
    }

    #[Test]
    public function returns_2_if_attorney_is_inactive_combined_format_lpastore(): void
    {
        $attorney = new LpaStoreAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstnames:   'A',
            name:         null,
            postcode:     null,
            surname:      'B',
            systemStatus: 'false',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::INACTIVE_ATTORNEY, ($status)($attorney));
    }
    #[Test]
    public function returns_2_if_attorney_is_inactive_combined_format_sirius(): void
    {
        $attorney = new SiriusLpaAttorney(
            addressLine1: '81 NighOnTimeWeBuiltIt Street',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1982-07-24'),
            email:        null,
            firstname:    'A',
            id:           '77',
            middlenames:  null,
            otherNames:   null,
            postcode:     null,
            surname:      'B',
            systemStatus: 'false',
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          '7',
        );

        $status = new GetAttorneyStatus(
            $this->loggerProphecy->reveal()
        );

        $this->assertEquals(AttorneyStatus::INACTIVE_ATTORNEY, ($status)($attorney));
    }
}
