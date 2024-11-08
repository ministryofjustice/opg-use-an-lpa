<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\Sirius\SiriusLpa;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use AppTest\Helper\EntityTestHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CombinedSiriusLpaTest extends TestCase
{
    public function getExpectedLpa(): SiriusLpa
    {
        return EntityTestHelper::makeSiriusLpa();

//        return new SiriusLpa(
//            $applicationHasGuidance     = false,
//            $applicationHasRestrictions = false,
//            $applicationType            = 'Classic',
//            $attorneyActDecisions       = null,
//            $attorneys                  = [
//                [
//                    'addressLine1' => '9 high street',
//                    'addressLine2' => '',
//                    'addressLine3' => '',
//                    'country'      => '',
//                    'county'       => '',
//                    'dob'          => null,
//                    'email'        => '',
//                    'firstname'    => 'jean',
//                    'firstnames'   => null,
//                    'name'         => null,
//                    'otherNames'   => null,
//                    'postcode'     => 'DN37 5SH',
//                    'surname'      => 'sanderson',
//                    'systemStatus' => '1',
//                    'town'         => '',
//                    'type'         => 'Primary',
//                    'uId'          => '700000000815',
//                ],
//                [
//                    'addressLine1' => '',
//                    'addressLine2' => '',
//                    'addressLine3' => '',
//                    'country'      => '',
//                    'county'       => '',
//                    'dob'          => null,
//                    'email'        => 'XXXXX',
//                    'firstname'    => 'Ann',
//                    'firstnames'   => null,
//                    'name'         => null,
//                    'otherNames'   => null,
//                    'postcode'     => '',
//                    'surname'      => 'Summers',
//                    'systemStatus' => '1',
//                    'town'         => '',
//                    'type'         => 'Primary',
//                    'uId'          => '7000-0000-0849',
//                ],
//            ],
//            $caseSubtype                = LpaType::fromShortName('personal-welfare'),
//            $channel                    = null,
//            $dispatchDate               = null,
//            $donor                      = (object)[
//                'addressLine1' => '81 Front Street',
//                'addressLine2' => 'LACEBY',
//                'addressLine3' => '',
//                'country'      => '',
//                'county'       => '',
//                'dob'          => null,
//                'email'        => 'RachelSanderson@opgtest.com',
//                'firstname'    => 'Rachel',
//                'firstnames'   => null,
//                'name'         => null,
//                'otherNames'   => null,
//                'postcode'     => 'DN37 5SH',
//                'surname'      => 'Sanderson',
//                'systemStatus' => null,
//                'town'         => '',
//                'type'         => 'Primary',
//                'uId'          => '700000000799',
//                'linked'       => [
//                    [
//                        'id'  => 7,
//                        'uId' => '700000000799',
//                    ],
//                ],
//            ],
//            $hasSeveranceWarning        = null,
//            $invalidDate                = null,
//            $lifeSustainingTreatment    = LifeSustainingTreatment::fromShortName('Option A'),
//            $lpaDonorSignatureDate      = new DateTimeImmutable('2012-12-12'),
//            $lpaIsCleansed              = true,
//            $onlineLpaId                = 'A33718377316',
//            $receiptDate                = new DateTimeImmutable('2014-09-26'),
//            $registrationDate           = new DateTimeImmutable('2019-10-10'),
//            $rejectedDate               = null,
//            $replacementAttorneys       = [],
//            $status                     = 'Registered',
//            $statusDate                 = null,
//            $trustCorporations          = [
//                [
//                    'addressLine1' => 'Street 1',
//                    'addressLine2' => 'Street 2',
//                    'addressLine3' => 'Street 3',
//                    'country'      => 'GB',
//                    'county'       => 'County',
//                    'dob'          => null,
//                    'email'        => null,
//                    'firstname'    => 'trust',
//                    'firstnames'   => null,
//                    'name'         => null,
//                    'otherNames'   => null,
//                    'postcode'     => 'ABC 123',
//                    'surname'      => 'test',
//                    'systemStatus' => '1',
//                    'town'         => 'Town',
//                    'type'         => 'Primary',
//                    'uId'          => '7000-0015-1998',
//                ],
//            ],
//            $uId                        = '700000000047',
//            $withdrawnDate              = null
//        );
    }

    #[Test]
    public function it_can_get_attorney(): void
    {
        $attorneys = [EntityTestHelper::makePerson(), EntityTestHelper::makePerson()];

        $siriusLpa = EntityTestHelper::makeSiriusLpa(
            attorneys: $attorneys
        );

        $result = $siriusLpa->getAttorneys();

        $this->assertSame($attorneys, $result);
    }

    #[Test]
    public function it_can_get_donor(): void
    {
        $donor = EntityTestHelper::makePerson();

        $siriusLpa = EntityTestHelper::makeSiriusLpa(
            donor: $donor
        );

        $result = $siriusLpa->getDonor();

        $this->assertSame($donor, $result);
    }

    #[Test]
    public function it_can_get_trust_corporation(): void
    {
        $trustCorporations = [EntityTestHelper::makePerson()];

        $siriusLpa = EntityTestHelper::makeSiriusLpa(
            trustCorporations: $trustCorporations
        );

        $result = $siriusLpa->getTrustCorporations();

        $this->assertSame($trustCorporations, $result);
    }

    #[Test]
    public function it_typecasts_on_getters(): void
    {
        $siriusLpa = EntityTestHelper::makeSiriusLpa(
            status: 'Registered',
            uId:    '700000000047'
        );

        $this->assertSame('700000000047', $siriusLpa->getUid());
        $this->assertSame('Registered', $siriusLpa->getStatus());
    }
}
