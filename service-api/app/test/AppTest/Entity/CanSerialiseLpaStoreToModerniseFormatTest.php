<?php

declare(strict_types=1);

namespace AppTest\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Service\Lpa\LpaDataFormatter;

class CanSerialiseLpaStoreToModerniseFormatTest extends TestCase
{
    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
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
                    'uId'          => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
                    'name'         => null,
                    'addressLine1' => '81 NighOnTimeWeBuiltIt Street',
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'country'      => 'GB',
                    'county'       => null,
                    'postcode'     => null,
                    'town'         => 'Mahhhhhhhhhh',
                    'type'         => null,
                    'dob'          => '1982-07-24 00:00:00.000000+0000',
                    'email'        => null,
                    'firstname'    => null,
                    'firstnames'   => 'Herman',
                    'surname'      => 'Seakrest',
                    'otherNames'   => null,
                    'systemStatus' => 'active',
                ],
            ],
            'caseSubtype'                => 'hw',
            'channel'                    => 'online',
            'dispatchDate'               => null,
            'donor'                      => [
                'uId'          => 'eda719db-8880-4dda-8c5d-bb9ea12c236f',
                'name'         => null,
                'addressLine1' => '74 Cloob Close',
                'addressLine2' => null,
                'addressLine3' => null,
                'country'      => 'GB',
                'county'       => null,
                'postcode'     => null,
                'town'         => 'Mahhhhhhhhhh',
                'type'         => null,
                'dob'          => '1970-01-24 00:00:00.000000+0000',
                'email'        => 'nobody@not.a.real.domain',
                'firstname'    => null,
                'firstnames'   => 'Feeg',
                'surname'      => 'Bundlaaaa',
                'otherNames'   => null,
                'systemStatus' => null,
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
                    'name'         => 'Trust us Corp.',
                    'uId'          => '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                    'addressLine1' => '103 Line 1',
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'country'      => 'GB',
                    'county'       => null,
                    'postcode'     => null,
                    'town'         => 'Town',
                    'type'         => null,
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => null,
                    'firstnames'   => null,
                    'surname'      => null,
                    'otherNames'   => null,
                    'systemStatus' => 'active',
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
