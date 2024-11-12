<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\AccessForAll;

use App\Entity\Lpa;
use App\Entity\Person;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Service\Lpa\AccessForAll\AccessForAllValidation;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccessForAllValidationTest extends TestCase
{
    private ActorMatch $actorMatch;
    private Lpa $lpa;

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

        $this->lpa = new Lpa(
            applicationHasGuidance: null,
            applicationHasRestrictions: null,
            applicationType: null,
            attorneyActDecisions: null,
            attorneys: [
                $this->actorMatch->actor,
            ],
            caseSubtype: null,
            channel: 'online',
            dispatchDate: null,
            donor: new Person(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country: null,
                county: null,
                dob: new DateTimeImmutable('1962-4-18', new DateTimeZone('UTC')),
                email: null,
                firstnames: null,
                name: null,
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
}
