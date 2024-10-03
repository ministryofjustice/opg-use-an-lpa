<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Service\Features\FeatureEnabled;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Service\Lpa\LpaDataFormatter;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CanSerialiseLpaStoreToModerniseFormatTest extends TestCase
{
    use ProphecyTrait;
    private LpaDataFormatter $lpaDataFormatter;
    private FeatureEnabled|ObjectProphecy $featureEnabled;

    public function setUp(): void
    {
        $this->featureEnabled = $this->prophesize(FeatureEnabled::class);
        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(false);
        $this->lpaDataFormatter = new LpaDataFormatter($this->featureEnabled->reveal());
    }

    #[Test]
    public function can_serialise_datastore_lpa_to_modernise_format(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/4UX3.json'), true);

        $expectedLpa = [
            'applicationHasGuidance'     => null,
            'applicationHasRestrictions' => null,
            'applicationType'            => null,
            'attorneyActDecisions'       => 'jointly',
            'attorneys'                  => [
                [
                    'addressLine1' => '81 NighOnTimeWeBuiltIt Street',
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'country'      => 'GB',
                    'county'       => null,
                    'dob'          => '1982-07-24 00:00:00.000000+0000',
                    'email'        => null,
                    'firstname'    => null,
                    'firstnames'   => 'Herman',
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => null,
                    'surname'      => 'Seakrest',
                    'systemStatus' => 'active',
                    'town'         => 'Mahhhhhhhhhh',
                    'type'         => null,
                    'uId'          => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
                ],
            ],
            'caseSubtype'                => 'hw',
            'channel'                    => 'online',
            'dispatchDate'               => null,
            'donor'                      => [
                'addressLine1' => '74 Cloob Close',
                'addressLine2' => null,
                'addressLine3' => null,
                'country'      => 'GB',
                'county'       => null,
                'dob'          => '1970-01-24 00:00:00.000000+0000',
                'email'        => 'nobody@not.a.real.domain',
                'firstname'    => null,
                'firstnames'   => 'Feeg',
                'name'         => null,
                'otherNames'   => null,
                'postcode'     => null,
                'surname'      => 'Bundlaaaa',
                'systemStatus' => null,
                'town'         => 'Mahhhhhhhhhh',
                'type'         => null,
                'uId'          => 'eda719db-8880-4dda-8c5d-bb9ea12c236f',
            ],
            'hasSeveranceWarning'        => null,
            'invalidDate'                => null,
            'lifeSustainingTreatment'    => 'option-a',
            'lpaDonorSignatureDate'      => '2024-01-10 23:00:00.000000+0000',
            'lpaIsCleansed'              => null,
            'onlineLpaId'                => null,
            'receiptDate'                => null,
            'registrationDate'           => '2024-01-12 00:00:00.000000+0000',
            'rejectedDate'               => null,
            'replacementAttorneys'       => null,
            'status'                     => 'registered',
            'statusDate'                 => null,
            'trustCorporations'          => [
                [
                    'addressLine1' => '103 Line 1',
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'country'      => 'GB',
                    'county'       => null,
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => null,
                    'firstnames'   => null,
                    'name'         => 'Trust us Corp.',
                    'otherNames'   => null,
                    'postcode'     => null,
                    'surname'      => null,
                    'systemStatus' => 'active',
                    'town'         => 'Town',
                    'type'         => null,
                    'uId'          => '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                    'companyName'  => 'Trust us Corp.',
                ],
            ],
            'uId'                        => 'M-789Q-P4DF-4UX3',
            'withdrawnDate'              => null,
        ];

        $newLpa = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }
}
