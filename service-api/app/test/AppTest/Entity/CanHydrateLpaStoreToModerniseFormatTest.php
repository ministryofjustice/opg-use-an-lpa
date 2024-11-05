<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\LpaStore\LpaStore;
use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CanHydrateLpaStoreToModerniseFormatTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;
    private FeatureEnabled|ObjectProphecy $featureEnabled;

    public function setUp(): void
    {
        $this->featureEnabled   = $this->prophesize(FeatureEnabled::class);
        $this->lpaDataFormatter = new LpaDataFormatter($this->featureEnabled->reveal());
    }

    public function expectedLpaStore(): LpaStore
    {
        return new LpaStore(
            applicationHasGuidance:     null,
            applicationHasRestrictions: null,
            applicationType:            null,
            attorneyActDecisions:       HowAttorneysMakeDecisions::tryFrom('jointly'),
            attorneys:                  [
                new LpaStoreAttorney(
                    addressLine1: '81 NighOnTimeWeBuiltIt Street',
                    addressLine2: null,
                    addressLine3: null,
                    country:      'GB',
                    county:       null,
                    dob:          new DateTimeImmutable('1982-07-24'),
                    email:        null,
                    firstname:    null,
                    firstnames:   'Herman',
                    name:         null,
                    otherNames:   null,
                    postcode:     null,
                    surname:      'Seakrest',
                    systemStatus: 'active',
                    town:         'Mahhhhhhhhhh',
                    type:         null,
                    uId:          '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d'
                ),
            ],
            caseSubtype:                LpaType::fromShortName('personal-welfare'),
            channel:                    'online',
            dispatchDate:               null,
            donor:                      new LpaStoreDonor(
                addressLine1:           '74 Cloob Close',
                addressLine2:           null,
                addressLine3:           null,
                country:                'GB',
                county:                 null,
                dob:                    new DateTimeImmutable('1970-01-24'),
                email:                  'nobody@not.a.real.domain',
                firstname:              null,
                firstnames:             'Feeg',
                name:                   null,
                otherNames:             null,
                postcode:               null,
                surname:                'Bundlaaaa',
                systemStatus:           null,
                town:                   'Mahhhhhhhhhh',
                type:                   null,
                uId:                    'eda719db-8880-4dda-8c5d-bb9ea12c236f'
            ),
            hasSeveranceWarning:     null,
            invalidDate:             null,
            lifeSustainingTreatment: LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate:   new DateTimeImmutable('2024-01-10T23:00:00Z'),
            lpaIsCleansed:           null,
            onlineLpaId:             null,
            receiptDate:             null,
            registrationDate:        new DateTimeImmutable('2024-01-12'),
            rejectedDate:            null,
            replacementAttorneys:    null,
            status:                  'registered',
            statusDate:              null,
            trustCorporations:       [
                new LpaStoreTrustCorporation(
                    addressLine1: '103 Line 1',
                    addressLine2: null,
                    addressLine3: null,
                    country:      'GB',
                    county:       null,
                    dob:          null,
                    email:        null,
                    firstname:    null,
                    firstnames:   null,
                    name:         'Trust us Corp.',
                    otherNames:   null,
                    postcode:     null,
                    surname:      null,
                    systemStatus: 'active',
                    town:         'Town',
                    type:         null,
                    uId:          '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                ),
            ],
            uId:                     'M-789Q-P4DF-4UX3',
            withdrawnDate:           null
        );
    }

    #[Test]
    public function can_hydrate_lpa_store_to_modernise_format(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/4UX3.json'), true);

        $expectedLpaStore = $this->expectedLpaStore();

        $combinedLpaStore = ($this->lpaDataFormatter)($lpa);

        $this->assertIsObject($combinedLpaStore);

        $this->assertEquals($expectedLpaStore, $combinedLpaStore);
    }
}
