<?php

declare(strict_types=1);

namespace CommonTest\Entity\LpaStore;

use Common\Service\Features\FeatureEnabled;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CanSerialiseSiriusToModerniseFormatTest extends TestCase
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
                    'dob'          => '1990-05-04 00:00:00.000000+0000',
                    'email'        => '',
                    'firstname'    => 'jean',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
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
                    'dob'          => '1975-10-05 00:00:00.000000+0000',
                    'email'        => 'XXXXX',
                    'firstname'    => 'Ann',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => '',
                    'surname'      => 'Summers',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '7000-0000-0849',
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
                'dob'          => '1948-11-01 00:00:00.000000+0000',
                'email'        => 'RachelSanderson@opgtest.com',
                'firstname'    => 'Rachel',
                'firstnames'   => null,
                'name'         => null,
                'otherNames'   => null,
                'postcode'     => 'DN37 5SH',
                'surname'      => 'Sanderson',
                'systemStatus' => null,
                'town'         => '',
                'type'         => 'Primary',
                'uId'          => '700000000799',
                'linked'       => [
                    [
                        'id'  => 7,
                        'uId' => '700000000799',
                    ],
                ],
            ],
            'hasSeveranceWarning'        => null,
            'invalidDate'                => null,
            'lifeSustainingTreatment'    => 'option-a',
            'lpaDonorSignatureDate'      => '2012-12-12 00:00:00.000000+0000',
            'lpaIsCleansed'              => true,
            'onlineLpaId'                => 'A33718377316',
            'receiptDate'                => '2014-09-26 00:00:00.000000+0000',
            'registrationDate'           => '2019-10-10 00:00:00.000000+0000',
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
                    'firstname'    => 'trust',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '7000-0015-1998',
                ],
            ],
            'uId'                        => '700000000047',
            'withdrawnDate'              => null,
        ];
    }

    #[Test]
    public function can_serialise_sirius_lpa_to_modernise_format(): void
    {
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(false);

        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);

        $expectedLpa = $this->getExpectedLpa();
        $newLpa      = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }

    #[Test]
    public function can_serialise_sirius_lpa_using_data_formatter(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);

        $expectedLpa   = $this->getExpectedLpa();
        $newLpa        = ($this->lpaDataFormatter)($lpa);
        $serialisedLpa = $this->lpaDataFormatter->serializeObject($newLpa);

        $this->assertEquals($expectedLpa, $serialisedLpa);
    }
}
