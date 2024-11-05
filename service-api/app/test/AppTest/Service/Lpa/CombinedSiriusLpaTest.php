<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CombinedSiriusLpaTest extends TestCase
{
    public function getExpectedLpa(): SiriusLpa
    {
        return new SiriusLpa(
            applicationHasGuidance:     false,
            applicationHasRestrictions: false,
            applicationType:            'Classic',
            attorneyActDecisions:       null,
            attorneys:                  [
                new SiriusLpaAttorney(
                    addressLine1: '9 high street',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        '',
                    firstname:    'jean',
                    firstnames:   null,
                    id:           '815',
                    name:         null,
                    otherNames:   null,
                    postcode:     'DN37 5SH',
                    surname:      'sanderson',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '700000000815',
                ),
                new SiriusLpaAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        'XXXXX',
                    firstname:    'Ann',
                    firstnames:   null,
                    id:           '849',
                    name:         null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'Summers',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '700000000849',
                ),
            ],
            caseSubtype:                LpaType::fromShortName('personal-welfare'),
            channel:                    null,
            dispatchDate:               null,
            donor:                      new SiriusLpaDonor(
                addressLine1: '81 Front Street',
                addressLine2: 'LACEBY',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          null,
                email:        'RachelSanderson@opgtest.com',
                firstname:    'Rachel',
                firstnames:   null,
                id:           '799',
                linked:       [
                                  [
                                      'id'  => 7,
                                      'uId' => '700000000799',
                                  ],
                              ],
                name:         null,
                otherNames:   null,
                postcode:     'DN37 5SH',
                surname:      'Sanderson',
                systemStatus: null,
                town:         '',
                type:         'Primary',
                uId:          '700000000799',
            ),
            hasSeveranceWarning:        null,
            invalidDate:                null,
            lifeSustainingTreatment:    LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate:      new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:              true,
            onlineLpaId:                'A33718377316',
            receiptDate:                new DateTimeImmutable('2014-09-26'),
            registrationDate:           new DateTimeImmutable('2019-10-10'),
            rejectedDate:               null,
            replacementAttorneys:       [],
            status:                     'Registered',
            statusDate:                 null,
            trustCorporations:          [
                new SiriusLpaTrustCorporation(
                    addressLine1:  'Street 1',
                    addressLine2:  'Street 2',
                    addressLine3:  'Street 3',
                    country:       'GB',
                    county:        'County',
                    dob:           null,
                    email:         null,
                    firstname:     'trust',
                    firstnames:    null,
                    id:            '998',
                    name:          null,
                    otherNames:    null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: '1',
                    town:         'Town',
                    type:         'Primary',
                    uId:          '700000151998',
                ),
            ],
            uId:                        '700000000047',
            withdrawnDate:              null
        );
    }

    #[Test]
    public function it_can_get_attorney(): void
    {
        $sut = $this->getExpectedLpa();

        $result = $sut->getAttorneys();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(SiriusLpaAttorney::class, $result);
    }

    #[Test]
    public function it_can_get_donor(): void
    {
        $sut = $this->getExpectedLpa();

        $result = $sut->getDonor();

        $this->assertInstanceOf(SiriusLpaDonor::class, $result);
    }

    #[Test]
    public function it_can_get_trust_corporation(): void
    {
        $sut = $this->getExpectedLpa();

        $result = $sut->getTrustCorporations();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(SiriusLpaTrustCorporation::class, $result);
    }
}
