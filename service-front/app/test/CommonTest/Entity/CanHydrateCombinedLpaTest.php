<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\CombinedLpa;
use Common\Entity\Person;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use CommonTest\Helper\EntityTestHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CanHydrateCombinedLpaTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    public function expectedSiriusLpa(): CombinedLpa
    {
        $donor = new Person(
            addressLine1            : '81 Front Street',
            addressLine2            : 'LACEBY',
            addressLine3            : '',
            country                 : '',
            county                  : '',
            dob                     : new DateTimeImmutable('1948-11-01'),
            email                   : 'RachelSanderson@opgtest.com',
            firstnames              : 'Rachel',
            name                    : null,
            otherNames              : 'Ezra',
            postcode                : 'DN37 5SH',
            surname                 : 'Sanderson',
            systemStatus            : 'true',
            town                    : '',
            uId                     : '700000000799',
            cannotMakeJointDecisions: null
        );

        $attorneys = [
            new Person(
                addressLine1            : '9 high street',
                addressLine2            : '',
                addressLine3            : '',
                country                 : '',
                county                  : '',
                dob                     : new DateTimeImmutable('1990-05-04'),
                email                   : '',
                firstnames              : null,
                name                    : null,
                otherNames              : null,
                postcode                : 'DN37 5SH',
                surname                 : 'sanderson',
                systemStatus            : 'active',
                town                    : '',
                uId                     : '700000000815',
                cannotMakeJointDecisions: true
            ),
            new Person(
                addressLine1            : '',
                addressLine2            : '',
                addressLine3            : '',
                country                 : '',
                county                  : '',
                dob                     : new DateTimeImmutable('1975-10-05'),
                email                   : 'XXXXX',
                firstnames              : null,
                name                    : null,
                otherNames              : null,
                postcode                : '',
                surname                 : 'Summers',
                systemStatus            : 'active',
                town                    : '',
                uId                     : '7000-0000-0849',
                cannotMakeJointDecisions: null
            ),
        ];

        $trustCorporations = [
            new Person(
                addressLine1            : 'Street 1',
                addressLine2            : 'Street 2',
                addressLine3            : 'Street 3',
                country                 : 'GB',
                county                  : 'County',
                dob                     : null,
                email                   : null,
                firstnames              : null,
                name                    : 'trust corporation',
                otherNames              : null,
                postcode                : 'ABC 123',
                surname                 : 'test',
                systemStatus            : 'active',
                town                    : 'Town',
                uId                     : '7000-0015-1998',
                cannotMakeJointDecisions: true
            ),
        ];

        $replacementAttorneys = [
            new Person(
                addressLine1            : 'Address Line 1',
                addressLine2            : 'Address Line 2',
                addressLine3            : 'Address Line 3',
                country                 : 'Country',
                county                  : 'County',
                dob                     : new DateTimeImmutable('2000-01-31'),
                email                   : 'email@example.com',
                firstnames              : 'Firstnames',
                name                    : 'Name',
                otherNames              : 'Other names',
                postcode                : 'Postcode',
                surname                 : 'Surname',
                systemStatus            : 'replacement',
                town                    : 'Town',
                uId                     : '7000-0000-0849',
                cannotMakeJointDecisions: null
            ),
        ];

        return EntityTestHelper::makeCombinedLpa(
            attorneys:                 $attorneys,
            channel:                   null,
            donor:                     $donor,
            hasSeveranceWarning: true,
            howAttorneysMakeDecisions: 'jointly-for-some-severally-for-others',
            howAttorneysMakeDecisionsDetails: 'This is mock data on how decisions are made"',
            lpaDonorSignatureDate:     new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:             true,
            onlineLpaId:               'A33718377316',
            receiptDate:               new DateTimeImmutable('2014-09-26'),
            registrationDate:          new DateTimeImmutable('2019-10-10'),
            replacementAttorneys:      $replacementAttorneys,
            restrictionsAndConditions: 'my restrictions and conditions',
            trustCorporations:         $trustCorporations,
            uId:                       '700000000047',
            whenTheLpaCanBeUsed:       WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY,
        );
    }

    #[Test]
    public function can_hydrate_sirius_lpa_to_modernise_format(): void
    {
        $lpa = json_decode(
            file_get_contents(
                __DIR__ . '../../../../test/fixtures/combined_lpa.json'
            ),
            true
        );

        $expectedSiriusLpa = $this->expectedSiriusLpa();

        $combinedSiriusLpa = ($this->lpaDataFormatter)($lpa);

        $this->assertIsObject($combinedSiriusLpa);

        $this->assertEquals($expectedSiriusLpa, $combinedSiriusLpa);
    }
}
