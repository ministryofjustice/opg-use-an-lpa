<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaDataFormatter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CanSerialiseSiriusToCombinedFormatTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;
    private FeatureEnabled|ObjectProphecy $featureEnabled;

    public function setUp(): void
    {
        $this->featureEnabled   = $this->prophesize(FeatureEnabled::class);
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    private function getExpectedLpa(): array
    {
        return [
            'applicationHasGuidance'     => false,
            'applicationHasRestrictions' => false,
            'applicationType'            => 'Classic',
            'attorneyActDecisions'       => null,
            'attorneys'                  => [
                [
                    'addressLine1' => '9 high street',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => '1990-05-04',
                    'email'        => '',
                    'firstnames'    => 'jean',
                    'name'         => null,
                    'postcode'     => 'DN37 5SH',
                    'surname'      => 'sanderson',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '700000000815',
                ],
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => '1975-10-05',
                    'email'        => 'XXXXX',
                    'firstnames'   => 'Ann',
                    'name'         => null,
                    'postcode'     => '',
                    'surname'      => 'Summers',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '700000000849',
                ],
            ],
            'caseSubtype'                => 'hw',
            'channel'                    => null,
            'dispatchDate'               => null,
            'donor'                      => [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => '',
                'country'      => '',
                'county'       => '',
                'dob'          => '1948-11-01',
                'email'        => 'RachelSanderson@opgtest.com',
                'firstnames'   => 'Rachel Emma',
                'name'         => null,
                'postcode'     => 'DN37 5SH',
                'surname'      => 'Sanderson',
                'systemStatus' => null,
                'town'         => '',
                'type'         => 'Primary',
                'uId'          => '700000000799',
            ],
            'hasSeveranceWarning'        => null,
            'invalidDate'                => null,
            'lifeSustainingTreatment'    => 'option-a',
            'lpaDonorSignatureDate'      => '2012-12-12T00:00:00Z',
            'lpaIsCleansed'              => true,
            'onlineLpaId'                => 'A33718377316',
            'receiptDate'                => '2014-09-26T00:00:00Z',
            'registrationDate'           => '2019-10-10T00:00:00Z',
            'rejectedDate'               => null,
            'replacementAttorneys'       => [],
            'status'                     => 'Registered',
            'statusDate'                 => null,
            'trustCorporations'          => [
                [
                    'addressLine1' => 'Street 1',
                    'addressLine2' => 'Street 2',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'County',
                    'dob'          => null,
                    'email'        => null,
                    'firstnames'   => 'trust',
                    'name'         => 'trust corporation',
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '700000151998',
                ],
            ],
            'uId'                        => '700000000047',
            'withdrawnDate'              => null,
        ];
    }

    #[Test]
    public function can_serialise_sirius_lpa_to_combined_format(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(false);

        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);

        $expectedLpa = $this->getExpectedLpa();
        $newLpa      = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }
}