<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
use App\Enum\ActorStatus;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CanHydrateSiriusToModerniseFormatTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    public function expectedSiriusLpa(): SiriusLpa
    {
        return new SiriusLpa(
            applicationHasGuidance:                    false,
            applicationHasRestrictions:                false,
            applicationType:                           'Classic',
            attorneys:                                 [
                new SiriusLpaAttorney(
                    addressLine1: '9 high street',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          new DateTimeImmutable('1990-05-04'),
                    email:        '',
                    firstname:    'jean',
                    id:           '9',
                    middlenames:  '',
                    otherNames:   null,
                    postcode:     'DN37 5SH',
                    surname:      'sanderson',
                    systemStatus: ActorStatus::ACTIVE,
                    town:         '',
                    uId:          '700000000815'
                ),
                new SiriusLpaAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          new DateTimeImmutable('1975-10-05'),
                    email:        'XXXXX',
                    firstname:    'Ann',
                    id:           '12',
                    middlenames:  null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'Summers',
                    systemStatus: ActorStatus::ACTIVE,
                    town:         '',
                    uId:          '700000000849'
                ),
            ],
            caseAttorneyJointly:                       false,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           true,
            caseSubtype:                               LpaType::fromShortName('personal-welfare'),
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     new SiriusLpaDonor(
                addressLine1: '81 Front Street',
                addressLine2: 'LACEBY',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          new DateTimeImmutable('1948-11-01'),
                email:        'RachelSanderson@opgtest.com',
                firstname:    'Rachel',
                id:           '7',
                linked:       [
                    [
                        'id'  => 7,
                        'uId' => '700000000799',
                    ],
                ],
                middlenames:  'Emma',
                otherNames:   null,
                postcode:     'DN37 5SH',
                surname:      'Sanderson',
                systemStatus: null,
                town:         '',
                uId:          '700000000799'
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
                new SiriusLpaTrustCorporation(
                    addressLine1: 'Street 1',
                    addressLine2: 'Street 2',
                    addressLine3: 'Street 3',
                    companyName:  'trust corporation',
                    country:      'GB',
                    county:       'County',
                    dob:          null,
                    email:        null,
                    firstname:    'trust',
                    id:           '3485',
                    middlenames:  null,
                    otherNames:   null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: ActorStatus::ACTIVE,
                    town:         'Town',
                    uId:          '700000151998',
                ),
            ],
            uId:                                       '700000000047',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );
    }

    #[Test]
    public function can_hydrate_sirius_lpa_to_modernise_format(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);

        $expectedSiriusLpa = $this->expectedSiriusLpa();

        $combinedSiriusLpa = ($this->lpaDataFormatter)($lpa);

        $this->assertEquals($expectedSiriusLpa, $combinedSiriusLpa);
    }
}
