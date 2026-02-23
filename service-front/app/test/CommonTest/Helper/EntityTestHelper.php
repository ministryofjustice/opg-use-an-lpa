<?php

declare(strict_types=1);

namespace CommonTest\Helper;

use Common\Entity\CaseActor;
use Common\Entity\CombinedLpa;
use Common\Entity\Lpa as SiriusLpa;
use Common\Entity\Person;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;

class EntityTestHelper
{
    public static function makeCaseActor(
        ?string $uId = '700000001234',
    ): CaseActor {
        $actor = new CaseActor();
        $actor->setUId($uId);

        return $actor;
    }

    public static function makeSiriusLpa(
        ?string $uId = '700000000047',
    ): SiriusLpa {
        $lpa = new SiriusLpa();
        $lpa->setUId($uId);

        return $lpa;
    }

    public static function makePerson(
        ?string $addressLine1 = 'Address Line 1',
        ?string $addressLine2 = 'Address Line 2',
        ?string $addressLine3 = 'Address Line 3',
        ?string $country = 'Country',
        ?string $county = 'County',
        ?DateTimeImmutable $dob = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?string $email = 'email@example.com',
        ?string $firstnames = 'Firstnames',
        ?string $name = 'Name',
        ?string $otherNames = 'Other names',
        ?string $postcode = 'Postcode',
        ?string $surname = 'Surname',
        ?string $systemStatus = 'System status',
        ?string $town = 'Town',
        ?string $uId = 'UID',
    ): Person {
        return new Person(
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            addressLine3: $addressLine3,
            cannotMakeJointDecisions: true,
            country: $country,
            county: $county,
            dob: $dob,
            email: $email,
            firstnames: $firstnames,
            name: $name,
            otherNames: $otherNames,
            postcode: $postcode,
            surname: $surname,
            systemStatus: $systemStatus,
            town: $town,
            uId: $uId,
        );
    }

    public static function makeCombinedLpa(
        ?bool $applicationHasGuidance = false,
        ?bool $applicationHasRestrictions = false,
        ?string $applicationType = 'Classic',
        ?array $attorneys = [],
        ?LpaType $caseSubtype = LpaType::PERSONAL_WELFARE,
        ?string $channel = 'online',
        ?DateTimeImmutable $dispatchDate = null,
        ?Person $donor = null,
        ?bool $hasSeveranceWarning = null,
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions = HowAttorneysMakeDecisions::SINGULAR,
        ?string $howAttorneysMakeDecisionsDetails = null,
        ?array $howAttorneysMakeDecisionsDetailsImages = null,
        ?DateTimeImmutable $invalidDate = null,
        ?LifeSustainingTreatment $lifeSustainingTreatment = LifeSustainingTreatment::OPTION_A,
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?bool $lpaIsCleansed = true,
        ?string $onlineLpaId = 'onlineLpaId',
        ?DateTimeImmutable $receiptDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?DateTimeImmutable $rejectedDate = null,
        ?array $replacementAttorneys = [],
        ?string $restrictionsAndConditions = null,
        ?array $restrictionsAndConditionsImages = null,
        ?string $status = 'Registered',
        ?DateTimeImmutable $statusDate = null,
        ?array $trustCorporations = [],
        ?string $uId = 'uId',
        ?DateTimeImmutable $withdrawnDate = null,
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST,
    ): CombinedLpa {
        if (count($attorneys) === 0) {
            $attorneys[] = EntityTestHelper::MakePerson();
        }

        if (count($replacementAttorneys) === 0) {
            $replacementAttorneys[] = EntityTestHelper::MakePerson();
        }

        if (count($trustCorporations) === 0) {
            $trustCorporations[] = EntityTestHelper::MakePerson();
        }

        return new CombinedLpa(
            applicationHasGuidance: $applicationHasGuidance,
            applicationHasRestrictions: $applicationHasRestrictions,
            applicationType: $applicationType,
            attorneys: $attorneys,
            caseSubtype: $caseSubtype,
            channel: $channel,
            dispatchDate: $dispatchDate,
            donor: $donor,
            hasSeveranceWarning: $hasSeveranceWarning,
            howAttorneysMakeDecisions: $howAttorneysMakeDecisions,
            howAttorneysMakeDecisionsDetails: $howAttorneysMakeDecisionsDetails,
            howAttorneysMakeDecisionsDetailsImages: $howAttorneysMakeDecisionsDetailsImages,
            invalidDate: $invalidDate,
            lifeSustainingTreatment: $lifeSustainingTreatment,
            lpaDonorSignatureDate: $lpaDonorSignatureDate,
            lpaIsCleansed: $lpaIsCleansed,
            onlineLpaId: $onlineLpaId,
            receiptDate: $receiptDate,
            registrationDate: $registrationDate,
            rejectedDate: $rejectedDate,
            replacementAttorneys: $replacementAttorneys,
            restrictionsAndConditions: $restrictionsAndConditions,
            restrictionsAndConditionsImages: $restrictionsAndConditionsImages,
            status: $status,
            statusDate: $statusDate,
            trustCorporations: $trustCorporations,
            uId: $uId,
            whenTheLpaCanBeUsed: $whenTheLpaCanBeUsed,
            withdrawnDate: $withdrawnDate
        );
    }
}
