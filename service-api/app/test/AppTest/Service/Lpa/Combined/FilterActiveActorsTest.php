<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\Combined;

use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
use App\Enum\ActorStatus;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\Combined\FilterActiveActors;
use App\Service\Lpa\Combined\FilterActiveActorsInterface;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class FilterActiveActorsTest extends TestCase
{
    use ProphecyTrait;

    private GetAttorneyStatusInterface $attorney;
    private FilterActiveActors $filterActiveActors;
    private GetAttorneyStatus|ObjectProphecy $getAttorneyStatus;
    private GetTrustCorporationStatus|ObjectProphecy $getTrustCorporationStatus;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private FilterActiveActorsInterface $lpa;
    private GetTrustCorporationStatusInterface $trustCorporation;

    public function setUp(): void
    {
        $this->getAttorneyStatus         = $this->prophesize(GetAttorneyStatus::class);
        $this->getTrustCorporationStatus = $this->prophesize(GetTrustCorporationStatus::class);
        $this->loggerProphecy            = $this->prophesize(LoggerInterface::class);
        $this->filterActiveActors        = new FilterActiveActors(
            $this->getAttorneyStatus->reveal(),
            $this->getTrustCorporationStatus->reveal()
        );

        $this->attorney = new SiriusLpaAttorney(
            addressLine1: '9 high street',
            addressLine2: '',
            addressLine3: '',
            country:      '',
            county:       '',
            dob:          null,
            email:        '',
            firstname:    'A',
            id:           '345678901',
            middlenames:  null,
            otherNames:   null,
            postcode:     'DN37 5SH',
            surname:      'B',
            systemStatus: ActorStatus::ACTIVE,
            town:         '',
            uId:          '7345678901',
        );

        $this->trustCorporation = new SiriusLpaTrustCorporation(
            addressLine1: 'Street 1',
            addressLine2: 'Street 2',
            addressLine3: 'Street 3',
            companyName:  'A',
            country:      'GB',
            county:       'County',
            dob:          null,
            email:        null,
            firstname:    'trust',
            id:           '678901234',
            middlenames:  null,
            otherNames:   null,
            postcode:     'ABC 123',
            surname:      'test',
            systemStatus: ActorStatus::ACTIVE,
            town:         'Town',
            uId:          '7678901234',
        );

        $this->lpa = new SiriusLpa(
            applicationHasGuidance:                    false,
            applicationHasRestrictions:                false,
            applicationType:                           'Classic',
            attorneys:                                 [
                                                           $this->attorney,
                                                       ],
            caseAttorneyJointly:                       true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           false,
            caseSubtype:                               LpaType::PERSONAL_WELFARE,
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     new SiriusLpaDonor(
                addressLine1: '81 Front Street',
                addressLine2: 'LACEBY',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          null,
                email:        'RachelSanderson@opgtest.com',
                firstname:    'Rachel',
                id:           '123456789',
                linked:       [
                                 [
                                     'id'  => '123456789',
                                     'uId' => '7123456789',
                                 ],
                                 [
                                     'id'  => '234567890',
                                     'uId' => '7234567890',
                                 ],
                             ],
                middlenames:  null,
                otherNames:   null,
                postcode:     'DN37 5SH',
                surname:      'Sanderson',
                systemStatus: null,
                town:         '',
                uId:          '7123456789',
            ),
            hasSeveranceWarning:                       null,
            invalidDate:                               null,
            lifeSustainingTreatment:                   LifeSustainingTreatment::OPTION_A,
            lpaDonorSignatureDate:                     new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:                             true,
            onlineLpaId:                               'A33718377316',
            receiptDate:                               new DateTimeImmutable('2014-09-26'),
            registrationDate:                          new DateTimeImmutable('2019-10-10'),
            rejectedDate:                              null,
            replacementAttorneys:                      [],
            status:                                    'Registered',
            statusDate:                                null,
            trustCorporations:                         [
                                                           $this->trustCorporation,
                                                       ],
            uId:                                       '700000000047',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );
    }

    #[Test]
    public function test_filter_active_actors(): void
    {
        $this->getAttorneyStatus
            ->__invoke($this->attorney)
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getTrustCorporationStatus
            ->__invoke($this->trustCorporation)
            ->willReturn(TrustCorporationStatus::ACTIVE_TC);

        $result = ($this->filterActiveActors)($this->lpa);

        $this->assertInstanceOf(FilterActiveActorsInterface::class, $result);

        $this->assertCount(1, $result->getAttorneys());
        $this->assertCount(1, $result->getTrustCorporations());

        $this->assertSame($this->attorney, $result->getAttorneys()[0]);
        $this->assertSame($this->trustCorporation, $result->getTrustCorporations()[0]);
    }
}
