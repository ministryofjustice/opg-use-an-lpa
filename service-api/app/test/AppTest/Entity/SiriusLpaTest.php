<?php

declare(strict_types=1);

namespace AppTest\Entity;

use DateTimeImmutable;
use DateTimeZone;
use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Enum\LpaType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiriusLpaTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new SiriusLpa(
            applicationHasGuidance: null,
            applicationHasRestrictions: null,
            applicationType: null,
            attorneyActDecisions: null,
            attorneys: [
            new SiriusLpaAttorney(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country:      null,
                county:       null,
                dob:          new DateTimeImmutable('1962-4-18', new DateTimeZone('UTC')),
                email:        null,
                firstname:    null,
                id:           null,
                middlenames:  null,
                otherNames:   null,
                postcode:     null,
                surname:      null,
                systemStatus: null,
                town:         null,
                type:         null,
                uId:          '700000000012',
            ),
            ],
            caseSubtype: LpaType::PERSONAL_WELFARE,
            channel: 'online',
            dispatchDate: null,
            donor: new SiriusLpaDonor(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country:      null,
                county:       null,
                dob:          new DateTimeImmutable('1962-4-18', new DateTimeZone('UTC')),
                email:        null,
                firstname:    null,
                id:           null,
                linked:       null,
                middlenames:  null,
                otherNames:   null,
                postcode:     null,
                surname:      null,
                systemStatus: null,
                town:         null,
                type:         null,
                uId:          '700000000012',
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

        $this->assertInstanceOf(SiriusLpa::class, $sut);
    }

}
