<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\AccessForAll;

use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Enum\LpaType;
use App\Service\Lpa\AccessForAll\AccessForAllValidation;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\SiriusLpa as OldSiriusLpa;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccessForAllValidationTest extends TestCase
{
    private ActorMatch $actorMatch;
    private SiriusLpa|OldSiriusLpa $lpa;

    public function setUp(): void
    {
        $this->actorMatch = new ActorMatch(
            new SiriusLpaAttorney(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country: null,
                county: null,
                dob: new DateTimeImmutable('1970-10-14', new DateTimeZone('UTC')),
                email: null,
                firstname: 'Test',
                id: null,
                middlenames: null,
                otherNames: null,
                postcode: null,
                surname: 'Testerson',
                systemStatus: null,
                town: null,
                type: null,
                uId: '700000000011',
            ),
            'attorney',
            '700000000011',
        );

        $this->lpa = new SiriusLpa(
            applicationHasGuidance: null,
            applicationHasRestrictions: null,
            applicationType: null,
            attorneyActDecisions: null,
            attorneys: [
                $this->actorMatch->actor,
            ],
            caseSubtype: LpaType::PERSONAL_WELFARE,
            channel: 'online',
            dispatchDate: null,
            donor: new SiriusLpaDonor(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country: null,
                county: null,
                dob: new DateTimeImmutable('1962-4-18', new DateTimeZone('UTC')),
                email: null,
                firstname: null,
                id: null,
                linked: null,
                middlenames: null,
                otherNames: null,
                postcode: null,
                surname: null,
                systemStatus: null,
                town: null,
                type: null,
                uId: '700000000012',
            ),
            hasSeveranceWarning: null,
            invalidDate: null,
            lifeSustainingTreatment: null,
            lpaDonorSignatureDate: null,
            lpaIsCleansed: null,
            onlineLpaId: null,
            receiptDate: null,
            registrationDate: null,
            rejectedDate: null,
            replacementAttorneys: [],
            status: null,
            statusDate: null,
            trustCorporations: [],
            uId: '700000000001',
            withdrawnDate: null,
        );
    }

    #[Test]
    public function it_can_be_initialised(): void
    {
        $sut = new AccessForAllValidation(
            $this->actorMatch,
            $this->lpa,
        );

        $this->assertInstanceOf(AccessForAllValidation::class, $sut);
    }

    #[Test]
    public function serializes_as_expected(): void
    {
        // test serialization of ActorMatch too here because it doesn't have its own test class
        $jsonString = '{
              "actor" : {
                "addressLine1" : null,
                "addressLine2" : null,
                "addressLine3" : null,
                "country" : null,
                "county" : null,
                "dob" : "1970-10-14",
                "email" : null,
                "firstnames" : "Test",
                "name" : null,
                "postcode" : null,
                "surname" : "Testerson",
                "systemStatus" : null,
                "town" : null,
                "type" : null,
                "uId" : "700000000011"
              },
              "role" : "attorney",
              "lpa-uid" : "700000000011"
            }';
        $this->assertJsonStringEqualsJsonString($jsonString, json_encode($this->actorMatch));

        $sut = new AccessForAllValidation(
            $this->actorMatch,
            $this->lpa,
            'myActorToken'
        );

        $jsonString = '{
            "actor": {
                "addressLine1": null,
                "addressLine2": null,
                "addressLine3": null,
                "country": null,
                "county": null,
                "dob": "1970-10-14",
                "email": null,
                "firstnames": "Test",
                "name": null,
                "postcode": null,
                "surname": "Testerson",
                "systemStatus": null,
                "town": null,
                "type": null,
                "uId": "700000000011"
            },
            "attorney": {
                "firstname": "Test",
                "middlenames": "",
                "surname": "Testerson",
                "uId": "700000000011"
            },
            "caseSubtype": "hw",
            "donor": {
                "firstname": "",
                "middlenames": "",
                "surname": "",
                "uId": "700000000012"
            },
            "lpa-id": "700000000011",
            "role": "attorney",
            "lpaActorToken": "myActorToken"
        }';
        $this->assertJsonStringEqualsJsonString($jsonString, json_encode($sut));
    }
}
