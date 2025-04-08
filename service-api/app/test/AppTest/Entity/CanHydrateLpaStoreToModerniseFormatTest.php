<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\LpaStore\LpaStore;
use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Enum\ActorStatus;
use App\Enum\HowAttorneysMakeDecisions;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
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

    private FeatureEnabled|ObjectProphecy $featureEnabled;
    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_hydrate_lpa_store_to_modernise_format(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/4UX3.json'), true);

        $expectedLpaStore = $this->expectedLpaStore();

        $combinedLpaStore = ($this->lpaDataFormatter)($lpa);

        $this->assertIsObject($combinedLpaStore);

        $this->assertEquals($expectedLpaStore, $combinedLpaStore);
    }

    public function expectedLpaStore(): LpaStore
    {
        return new LpaStore(
            attorneys:                 [
                new LpaStoreAttorney(
                    line1:       '81 NighOnTimeWeBuiltIt Street',
                    line2:       null,
                    line3:       null,
                    country:     'GB',
                    county:      null,
                    dateOfBirth: new DateTimeImmutable('1982-07-24'),
                    email:       null,
                    firstNames:  'Herman',
                    postcode:    null,
                    lastName:    'Seakrest',
                    status:      ActorStatus::ACTIVE,
                    town:        'Mahhhhhhhhhh',
                    uId:         '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d'
                ),
                                       ],
            caseSubtype:               LpaType::PERSONAL_WELFARE,
            channel:                   'online',
            donor:                     new LpaStoreDonor(
                line1:             '74 Cloob Close',
                line2:             null,
                line3:             null,
                country:           'GB',
                county:            null,
                dateOfBirth:       new DateTimeImmutable('1970-01-24'),
                email:             'nobody@not.a.real.domain',
                firstNames:        'Feeg',
                otherNamesKnownBy: null,
                postcode:          null,
                lastName:          'Bundlaaaa',
                town:              'Mahhhhhhhhhh',
                uId:               'eda719db-8880-4dda-8c5d-bb9ea12c236f'
            ),
            howAttorneysMakeDecisions: HowAttorneysMakeDecisions::JOINTLY,
            lifeSustainingTreatment:   LifeSustainingTreatment::OPTION_A,
            signedAt:                  new DateTimeImmutable('2024-01-10T23:00:00Z'),
            registrationDate:          new DateTimeImmutable('2024-01-12'),
            restrictionsAndConditions: 'my restrictions and conditions',
            status:                    'registered',
            trustCorporations:         [
                new LpaStoreTrustCorporation(
                    line1:       '103 Line 1',
                    line2:       null,
                    line3:       null,
                    country:     'GB',
                    county:      null,
                    dateOfBirth: null,
                    email:       null,
                    firstNames:  null,
                    name:        'Trust us Corp.',
                    postcode:    null,
                    lastName:    null,
                    status:      ActorStatus::ACTIVE,
                    town:        'Town',
                    uId:         '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                ),
                                       ],
            uId:                       'M-789Q-P4DF-4UX3',
            updatedAt:                 new DateTimeImmutable('2024-01-12T23:00:00Z'),
            whenTheLpaCanBeUsed:       WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST,
        );
    }
}
