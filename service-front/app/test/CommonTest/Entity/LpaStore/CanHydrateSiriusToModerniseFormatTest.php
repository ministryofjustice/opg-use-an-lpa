<?php

declare(strict_types=1);

namespace CommonTest\Entity\LpaStore;

use Common\Entity\Sirius\SiriusLpa;
use Common\Entity\Sirius\SiriusLpaAttorney;
use Common\Entity\Sirius\SiriusLpaDonor;
use Common\Entity\Sirius\SiriusLpaTrustCorporations;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Service\Features\FeatureEnabled;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CanHydrateSiriusToModerniseFormatTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;
    private FeatureEnabled|ObjectProphecy $featureEnabled;

    public function setUp(): void
    {
        $this->featureEnabled   = $this->prophesize(FeatureEnabled::class);
        $this->lpaDataFormatter = new LpaDataFormatter($this->featureEnabled->reveal());
    }

    public function expectedSiriusLpa(): SiriusLpa
    {
        return new SiriusLpa(
            $applicationHasGuidance     = false,
            $applicationHasRestrictions = false,
            $applicationType            = 'Classic',
            $attorneyActDecisions       = null,
            $attorneys                  = [
                new SiriusLpaAttorney(
                    $addressLine1 = '9 high street',
                    $addressLine2 = '',
                    $addressLine3 = '',
                    $country      = '',
                    $county       = '',
                    $dob          = new DateTimeImmutable('1990-05-04'),
                    $email        = '',
                    $firstname    = 'jean',
                    $firstnames   = null,
                    $name         = null,
                    $otherNames   = null,
                    $postcode     = 'DN37 5SH',
                    $surname      = 'sanderson',
                    $systemStatus = '1',
                    $town         = '',
                    $type         = 'Primary',
                    $uId          = '700000000815'
                ),
                new SiriusLpaAttorney(
                    $addressLine1       = '',
                    $addressLine2       = '',
                    $addressLine3       = '',
                    $country            = '',
                    $county             = '',
                    $dob                = new DateTimeImmutable('1975-10-05'),
                    $email              = 'XXXXX',
                    $firstname          = 'Ann',
                    $firstnames         = null,
                    $name               = null,
                    $otherNames         = null,
                    $postcode           = '',
                    $surname            = 'Summers',
                    $systemStatus       = '1',
                    $town               = '',
                    $type               = 'Primary',
                    $uId                = '7000-0000-0849'
                ),
            ],
            $caseSubtype      = LpaType::fromShortName('personal-welfare'),
            $channel          = null,
            $dispatchDate     = null,
            $donor            = new SiriusLpaDonor(
                $addressLine1 = '81 Front Street',
                $addressLine2 = 'LACEBY',
                $addressLine3 = '',
                $country      = '',
                $county       = '',
                $dob          = new DateTimeImmutable('1948-11-01'),
                $email        = 'RachelSanderson@opgtest.com',
                $firstname    = 'Rachel',
                $firstnames   = null,
                $linked       = [
                    [
                        'id'  => 7,
                        'uId' => '700000000799',
                    ],
                ],
                $name         = null,
                $otherNames   = null,
                $postcode     = 'DN37 5SH',
                $surname      = 'Sanderson',
                $systemStatus = null,
                $town         = '',
                $type         = 'Primary',
                $uId          = '700000000799'
            ),
            $hasSeveranceWarning     = null,
            $invalidDate             = null,
            $lifeSustainingTreatment = LifeSustainingTreatment::fromShortName('Option A'),
            $lpaDonorSignatureDate   = new DateTimeImmutable('2012-12-12'),
            $lpaIsCleansed           = true,
            $onlineLpaId             = 'A33718377316',
            $receiptDate             = new DateTimeImmutable('2014-09-26'),
            $registrationDate        = new DateTimeImmutable('2019-10-10'),
            $rejectedDate            = null,
            $replacementAttorneys    = [],
            $status                  = 'Registered',
            $statusDate              = null,
            $trustCorporations       = [
                new SiriusLpaTrustCorporations(
                    $addressLine1 = 'Street 1',
                    $addressLine2 = 'Street 2',
                    $addressLine3 = 'Street 3',
                    $country      = 'GB',
                    $county       = 'County',
                    $dob          = null,
                    $email        = null,
                    $firstname    = 'trust',
                    $firstnames   = null,
                    $name         = null,
                    $otherNames   = null,
                    $postcode     = 'ABC 123',
                    $surname      = 'test',
                    $systemStatus = '1',
                    $town         = 'Town',
                    $type         = 'Primary',
                    $uId          = '7000-0015-1998',
                ),
            ],
            $uId                     = '700000000047',
            $withdrawnDate           = null
        );
    }

    #[Test]
    public function can_hydrate_sirius_lpa_to_modernise_format(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);

        $expectedSiriusLpa = $this->expectedSiriusLpa();

        $combinedSiriusLpa = ($this->lpaDataFormatter)($lpa);

        $this->assertIsObject($combinedSiriusLpa);

        $this->assertEquals($expectedSiriusLpa, $combinedSiriusLpa);
    }
}
