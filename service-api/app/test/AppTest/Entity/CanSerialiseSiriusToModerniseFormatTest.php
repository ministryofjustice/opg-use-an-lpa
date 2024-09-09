<?php

declare(strict_types=1);

namespace AppTest\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Service\Lpa\LpaDataFormatter;

class CanSerialiseSiriusToModerniseFormatTest extends TestCase
{
    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_serialise_datastore_lpa_to_modernise_format(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);

        $expectedLpa = [
            'applicationHasGuidance'     => false,
            'applicationHasRestrictions' => false,
            'applicationType'            => 'Classic',
            'attorneyActDecisions'       => null,
            'attorneys'                  => [
                [
                    'uId'          => '700000000815',
                    'name'         => null,
                    'addressLine1' => '9 high street',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'postcode'     => 'DN37 5SH',
                    'town'         => '',
                    'type'         => 'Primary',
                    'dob'          => '1990-05-04 00:00:00.000000+0000',
                    'email'        => '',
                    'firstname'    => 'jean',
                    'firstnames'   => null,
                    'surname'      => 'sanderson',
                    'otherNames'   => null,
                    'systemStatus' => '1',
                ],
                [
                    'uId'          => '7000-0000-0849',
                    'name'         => null,
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'postcode'     => '',
                    'town'         => '',
                    'type'         => 'Primary',
                    'dob'          => '1975-10-05 00:00:00.000000+0000',
                    'email'        => 'XXXXX',
                    'firstname'    => 'Ann',
                    'firstnames'   => null,
                    'surname'      => 'Summers',
                    'otherNames'   => null,
                    'systemStatus' => '1',
                ],
            ],
            'caseSubtype'                => 'hw',
            'channel'                    => null,
            'dispatchDate'               => null,
            'donor'                      => [
                'uId'          => '700000000799',
                'name'         => null,
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => '',
                'country'      => '',
                'county'       => '',
                'postcode'     => 'DN37 5SH',
                'town'         => '',
                'type'         => 'Primary',
                'dob'          => '1948-11-01 00:00:00.000000+0000',
                'email'        => 'RachelSanderson@opgtest.com',
                'firstname'    => 'Rachel',
                'firstnames'   => null,
                'surname'      => 'Sanderson',
                'otherNames'   => null,
                'systemStatus' => null,
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
            'trustCorporations'          => [],
            'uId'                        => '700000000047',
            'withdrawnDate'              => null,
        ];

        $newLpa = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }
}
