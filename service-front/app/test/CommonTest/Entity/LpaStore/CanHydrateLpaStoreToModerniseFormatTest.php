<?php

declare(strict_types=1);

namespace CommonTest\Entity\LpaStore;

use Common\Entity\LpaStore\LpaStore;
use Common\Entity\LpaStore\LpaStoreAttorney;
use Common\Entity\LpaStore\LpaStoreDonor;
use Common\Entity\LpaStore\LpaStoreTrustCorporations;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use Common\Service\Features\FeatureEnabled;
use CommonTest\Helper\EntityTestHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Common\Service\Lpa\Factory\LpaDataFormatter;
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
        $donor = new LpaStoreDonor(
            line1           : '74 Cloob Close',
            line2           : null,
            line3           : null,
            country                : 'GB',
            county                 : null,
            dob                    : new DateTimeImmutable('1970-01-24'),
            email                  : 'nobody@not.a.real.domain',
            firstname              : null,
            firstnames             : 'Feeg',
            name                   : null,
            otherNames             : null,
            postcode               : null,
            surname                : 'Bundlaaaa',
            systemStatus           : null,
            town                   : 'Mahhhhhhhhhh',
            type                   : null,
            uId                    : 'eda719db-8880-4dda-8c5d-bb9ea12c236f'
        );

        $attorneys = [
            new LpaStoreAttorney(
                line1 : '81 NighOnTimeWeBuiltIt Street',
                line2 : null,
                line3 : null,
                country      : 'GB',
                county       : null,
                dob          : new DateTimeImmutable('1982-07-24'),
                email        : null,
                firstname    : null,
                firstnames   : 'Herman',
                name         : null,
                otherNames   : null,
                postcode     : null,
                surname      : 'Seakrest',
                systemStatus : 'active',
                town         : 'Mahhhhhhhhhh',
                type         : null,
                uId          : '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d'
            ),
        ];

        $trustCorporations = [
            new LpaStoreTrustCorporations(
                line1 : '103 Line 1',
                line2 : null,
                line3 : null,
                companyName  : 'Trust us Corp.',
                country      : 'GB',
                county       : null,
                dob          : null,
                email        : null,
                firstname    : null,
                firstnames   : null,
                name         : 'Trust us Corp.',
                otherNames   : null,
                postcode     : null,
                surname      : null,
                systemStatus : 'active',
                town         : 'Town',
                type         : null,
                uId          : '1d95993a-ffbb-484c-b2fe-f4cca51801da',
            ),
        ];

        return EntityTestHelper::makeLpaStoreLpa(
            attorneys:                 $attorneys,
            donor:                     $donor,
            howAttorneysMakeDecisions: HowAttorneysMakeDecisions::JOINTLY,
            lpaDonorSignatureDate:     new DateTimeImmutable('2024-01-10 23:00:00'),
            registrationDate:          new DateTimeImmutable('2024-01-12'),
            status:                    'registered',
            trustCorporations:         $trustCorporations,
            uId:                       'M-789Q-P4DF-4UX3',
        );
    }

    #[Test]
    public function can_hydrate_lpa_store_to_modernise_format(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/4UX3.json'), true);

        $combinedLpaStore = ($this->lpaDataFormatter)($lpa);

        $this->assertIsObject($combinedLpaStore);

        $this->assertEquals($this->expectedLpaStore(), $combinedLpaStore);
    }
}
