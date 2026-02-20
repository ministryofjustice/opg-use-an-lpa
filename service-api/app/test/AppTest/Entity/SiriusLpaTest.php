<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Enum\LpaType;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\LpaAlreadyAdded\DonorInformationInterface;
use App\Service\Lpa\LpaAlreadyAdded\LpaAlreadyAddedInterface;
use App\Service\Lpa\ResolveActor\ResolveActorInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertInstanceOf;

class SiriusLpaTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new SiriusLpa(
            applicationHasGuidance:     null,
            applicationHasRestrictions: null,
            applicationType:            null,
            attorneys:                  [
                new SiriusLpaAttorney(
                    addressLine1: null,
                    addressLine2: null,
                    addressLine3: null,
                    cannotMakeJointDecisions: true,
                    country:      null,
                    county:       null,
                    dob:          new DateTimeImmutable(
                        '1962-4-18',
                        new DateTimeZone('UTC')
                    ),
                    email:        null,
                    firstname:    null,
                    id:           null,
                    middlenames:  null,
                    otherNames:   null,
                    postcode:     null,
                    surname:      null,
                    systemStatus: null,
                    town:         null,
                    uId:          '700000000012',
                ),
            ],
            caseAttorneyJointly: true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally: false,
            caseSubtype:                LpaType::PERSONAL_WELFARE,
            channel:                    'online',
            dispatchDate:               null,
            donor:                      new SiriusLpaDonor(
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
                uId:          '700000000012',
            ),
            hasSeveranceWarning:        null,
            invalidDate:                null,
            lifeSustainingTreatment:    null,
            lpaDonorSignatureDate:      null,
            lpaIsCleansed:              null,
            onlineLpaId:                null,
            receiptDate:                null,
            registrationDate:           null,
            rejectedDate:               null,
            replacementAttorneys:       [],
            status:                     null,
            statusDate:                 null,
            trustCorporations:          [],
            uId:                        '700000000001',
            whenTheLpaCanBeUsed:        null,
            withdrawnDate:              null,
        );

        $donor = $sut->getDonor();
        $this->assertInstanceOf(DonorInformationInterface::class, $donor);
        $this->assertInstanceOf(GetAttorneyStatusInterface::class, $donor);
        $this->assertInstanceOf(ResolveActorInterface::class, $donor);
        $this->assertInstanceOf(ActorMatchingInterface::class, $donor);

        $this->assertInstanceOf(SiriusLpa::class, $sut);
    }
}
