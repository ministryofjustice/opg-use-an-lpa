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

        $expectedLpa =  [
            "applicationHasGuidance" => false,
            "applicationHasRestrictions" => false,
            "applicationType" => "Classic",
            "attorneyActDecisions" => null,
            "attorneys" => [
                [
                    "name" => null,
                    "addressLine1" => null,
                    "addressLine2" => null,
                    "addressLine3" => null,
                    "country" => null,
                    "county" => null,
                    "postcode" => null,
                    "town" => null,
                    "type" => null,
                    "dob" => null,
                    "email" => "",
                    "firstname" => "jean",
                    "firstnames" => null,
                    "surname" => null,
                    "otherNames" => null,
                    "systemStatus" => null,
                ],
                [
                    "name" => null,
                    "addressLine1" => null,
                    "addressLine2" => null,
                    "addressLine3" => null,
                    "country" => null,
                    "county" => null,
                    "postcode" => null,
                    "town" => null,
                    "type" => null,
                    "dob" => null,
                    "email" => "XXXXX",
                    "firstname" => "Ann",
                    "firstnames" => null,
                    "surname" => null,
                    "otherNames" => null,
                    "systemStatus" => null,
                ],
            ],
            "caseSubtype" => null,
            "channel" => null,
            "dispatchDate" => null,
            "donor" => [
                "name" => null,
                "addressLine1" => null,
                "addressLine2" => null,
                "addressLine3" => null,
                "country" => null,
                "county" => null,
                "postcode" => null,
                "town" => null,
                "type" => null,
                "dob" => null,
                "email" => "RachelSanderson@opgtest.com",
                "firstname" => "Rachel",
                "firstnames" => null,
                "surname" => null,
                "otherNames" => null,
                "systemStatus" => null,
            ],
            "hasSeveranceWarning" => null,
            "invalidDate" => null,
            "lifeSustainingTreatment" => null,
            "lpaDonorSignatureDate" => null,
            "lpaIsCleansed" => true,
            "onlineLpaId" => "A33718377316",
            "receiptDate" => "2014-09-26T00:00:00+00:00",
            "registrationDate" => "2019-10-10T00:00:00+00:00",
            "rejectedDate" => null,
            "replacementAttorneys" => [],
            "status" => "Registered",
            "statusDate" => null,
            "trustCorporations" => [],
            "uId" => "700000000047",
            "withdrawnDate" => null,
        ];

        $newLpa = ($this->lpaDataFormatter)($lpa);

        $jsonLpa         = json_encode($newLpa);
        $expectedJsonLpa = json_encode($expectedLpa);

        $this->assertEquals($expectedJsonLpa, $jsonLpa);
    }
}