<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\LpaStore\LpaStore;
use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Enum\LpaType;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaStoreTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new LpaStore(
            attorneys: [
                new LpaStoreAttorney(
                    line1: null,
                    line2: null,
                    line3: null,
                    country: null,
                    county: null,
                    dateOfBirth: null,
                    email: null,
                    firstNames: null,
                    postcode: null,
                    lastName: null,
                    status: null,
                    town: null,
                    uId: '700000000012',
                    cannotMakeJointDecisions: null,
                ),
            ],
            caseSubtype: LpaType::PERSONAL_WELFARE,
            channel: 'online',
            donor: new LpaStoreDonor(
                line1: null,
                line2: null,
                line3: null,
                country: null,
                county: null,
                dateOfBirth: null,
                email: null,
                firstNames: null,
                otherNamesKnownBy: null,
                postcode: null,
                lastName: null,
                town: null,
                uId: '700000000012',
                cannotMakeJointDecisions: null,
            ),
            howAttorneysMakeDecisions: null,
            howAttorneysMakeDecisionsDetails: null,
            howAttorneysMakeDecisionsDetailsImages: null,
            lifeSustainingTreatment: null,
            signedAt: new DateTimeImmutable('2024-4-18', new DateTimeZone('UTC')),
            registrationDate: new DateTimeImmutable('2024-4-18', new DateTimeZone('UTC')),
            restrictionsAndConditions: null,
            restrictionsAndConditionsImages: [],
            status: '',
            trustCorporations: [],
            uId: '700000000001',
            updatedAt: new DateTimeImmutable('2024-4-18', new DateTimeZone('UTC')),
            whenTheLpaCanBeUsed: null,
        );

        $this->assertInstanceOf(LpaStore::class, $sut);
    }
}
