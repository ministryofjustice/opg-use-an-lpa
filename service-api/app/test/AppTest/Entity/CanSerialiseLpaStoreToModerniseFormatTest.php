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
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);

        $lpa['trustCorporations'] = [
            [
                'uid'           => '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                'name'          => 'Trust us Corp.',
                'companyNumber' => '666123321',
                'address'       => [
                    'line1'   => '103 Line 1',
                    'town'    => 'Town',
                    'country' => 'GB',
                ],
                'status'        => 'active',
                'channel'       => 'paper',
                'signedAt'      => '2024-01-10',
            ],
        ];

        $expectedLpa = [
            'applicationHasGuidance' => false,
            'applicationHasRestrictions' => false,
            'applicationType' => 'Classic',
            'attorneyActDecisions' => NULL,
            'attorneys' => [
                0 => [
                    'name' => NULL,
                    'addressLine1' => NULL,
                    'addressLine2' => NULL,
                    'addressLine3' => NULL,
                    'country' => NULL,
                    'county' => NULL,
                    'postcode' => NULL,
                    'town' => NULL,
                    'type' => NULL,
                    'dob' => NULL,
                    'email' => '',
                    'firstname' => 'jean',
                    'firstnames' => NULL,
                    'surname' => NULL,
                    'otherNames' => NULL,
                    'systemStatus' => NULL,
                ],
                1 => [
                    'name' => NULL,
                    'addressLine1' => NULL,
                    'addressLine2' => NULL,
                    'addressLine3' => NULL,
                    'country' => NULL,
                    'county' => NULL,
                    'postcode' => NULL,
                    'town' => NULL,
                    'type' => NULL,
                    'dob' => NULL,
                    'email' => 'XXXXX',
                    'firstname' => 'Ann',
                    'firstnames' => NULL,
                    'surname' => NULL,
                    'otherNames' => NULL,
                    'systemStatus' => NULL,
                ],
            ],
            'caseSubtype' => NULL,
            'channel' => NULL,
            'dispatchDate' => NULL,
            'donor' => [
                'name' => NULL,
                'addressLine1' => NULL,
                'addressLine2' => NULL,
                'addressLine3' => NULL,
                'country' => NULL,
                'county' => NULL,
                'postcode' => NULL,
                'town' => NULL,
                'type' => NULL,
                'dob' => NULL,
                'email' => 'RachelSanderson@opgtest.com',
                'firstname' => 'Rachel',
                'firstnames' => NULL,
                'surname' => NULL,
                'otherNames' => NULL,
                'systemStatus' => NULL,
            ],
            'hasSeveranceWarning' => NULL,
            'invalidDate' => NULL,
            'lifeSustainingTreatment' => NULL,
            'lpaDonorSignatureDate' => NULL,
            'lpaIsCleansed' => true,
            'onlineLpaId' => 'A33718377316',
            'receiptDate' => '2014-09-26T00:00:00+00:00',
            'registrationDate' => '2019-10-10T00:00:00+00:00',
            'rejectedDate' => NULL,
            'replacementAttorneys' => [
            ],
            'status' => 'Registered',
            'statusDate' => NULL,
            'trustCorporations' => [
                0 => [
                    'name' => 'Trust us Corp.',
                    'addressLine1' => '103 Line 1',
                    'addressLine2' => NULL,
                    'addressLine3' => NULL,
                    'country' => 'GB',
                    'county' => NULL,
                    'postcode' => NULL,
                    'town' => 'Town',
                    'type' => NULL,
                    'dob' => NULL,
                    'email' => NULL,
                    'firstname' => NULL,
                    'firstnames' => NULL,
                    'surname' => NULL,
                    'otherNames' => NULL,
                    'systemStatus' => 'active',
                    'companyName' => 'Trust us Corp.',
                ],
            ],
            'uId' => '700000000047',
            'withdrawnDate' => NULL,
        ];

        $newLpa = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }
}