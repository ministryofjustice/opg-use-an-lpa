<?php

declare(strict_types=1);

namespace CommonTest\Helper;

use Common\Entity\CombinedLpa;
use Common\Entity\LpaStore\LpaStore;
use Common\Entity\Person;
use Common\Entity\Sirius\SiriusLpa;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaStatus;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use DateTimeImmutable;

class EntityTestHelper
{
    public static function makePerson(
        ?string $line1 = 'Address Line 1',
        ?string $line2 = 'Address Line 2',
        ?string $line3 = 'Address Line 3',
        ?string $country = 'Country',
        ?string $county = 'County',
        ?DateTimeImmutable $dob = new DateTimeImmutable(TestData::testDateString),
        ?string $email = 'email@example.com',
        ?string $firstname = 'Firstname',
        ?string $firstnames = 'Firstnames',
        ?string $name = 'Name',
        ?string $otherNames = 'Other names',
        ?string $postcode = 'Postcode',
        ?string $surname = 'Surname',
        ?string $systemStatus = 'System status',
        ?string $town = 'Town',
        ?string $type = 'Type',
        ?string $uId = 'UID',
    ): Person {
        return new Person(
            line1: $line1,
            line2: $line2,
            line3: $line3,
            country:      $country,
            county:       $county,
            dob:          $dob,
            email:        $email,
            firstname:    $firstname,
            firstnames:   $firstnames,
            name:         $name,
            otherNames:   $otherNames,
            postcode:     $postcode,
            surname:      $surname,
            systemStatus: $systemStatus,
            town:         $town,
            type:         $type,
            uId:          $uId
        );
    }

    public static function makeCombinedLpa(
        ?bool $applicationHasGuidance = true,
        ?bool $applicationHasRestrictions = true,
        ?string $applicationType = 'applicationType',
        ?array $attorneys = [],
        ?LpaType $caseSubtype = LpaType::PERSONAL_WELFARE,
        ?string $channel = 'channel',
        ?DateTimeImmutable $dispatchDate = new DateTimeImmutable(TestData::testDateString),
        ?Person $donor = null,
        ?bool $hasSeveranceWarning = true,
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions = HowAttorneysMakeDecisions::SINGULAR,
        ?DateTimeImmutable $invalidDate = new DateTimeImmutable(TestData::testDateString),
        ?LifeSustainingTreatment $lifeSustainingTreatment = LifeSustainingTreatment::OPTION_A,
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::testDateString),
        ?bool $lpaIsCleansed = true,
        ?string $onlineLpaId = 'onlineLpaId',
        ?DateTimeImmutable $receiptDate = new DateTimeImmutable(TestData::testDateString),
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::testDateString),
        ?DateTimeImmutable $rejectedDate = new DateTimeImmutable(TestData::testDateString),
        ?array $replacementAttorneys = [],
        ?string $status = 'Registered',
        ?DateTimeImmutable $statusDate = new DateTimeImmutable(TestData::testDateString),
        ?array $trustCorporations = [],
        ?string $uId = 'uId',
        ?DateTimeImmutable $withdrawnDate = new DateTimeImmutable(TestData::testDateString),
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
            invalidDate: $invalidDate,
            lifeSustainingTreatment: $lifeSustainingTreatment,
            lpaDonorSignatureDate: $lpaDonorSignatureDate,
            lpaIsCleansed: $lpaIsCleansed,
            onlineLpaId: $onlineLpaId,
            receiptDate: $receiptDate,
            registrationDate: $registrationDate,
            rejectedDate: $rejectedDate,
            replacementAttorneys: $replacementAttorneys,
            status: $status,
            statusDate: $statusDate,
            trustCorporations: $trustCorporations,
            uId: $uId,
            withdrawnDate: $withdrawnDate,
            whenTheLpaCanBeUsed: $whenTheLpaCanBeUsed
        );
    }

    public static function makeSiriusLpa(
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
        ?DateTimeImmutable $invalidDate = null,
        ?LifeSustainingTreatment $lifeSustainingTreatment = LifeSustainingTreatment::OPTION_A,
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::testDateString),
        ?bool $lpaIsCleansed = null,
        ?string $onlineLpaId = null,
        ?DateTimeImmutable $receiptDate = null,
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::testDateString),
        ?DateTimeImmutable $rejectedDate = null,
        ?array $replacementAttorneys = null,
        ?string $status = 'Registered',
        ?DateTimeImmutable $statusDate = null,
        ?array $trustCorporations = [],
        ?string $uId = 'uId',
        ?DateTimeImmutable $withdrawnDate = null,
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST,
    ): SiriusLpa {
        return new SiriusLpa(
            applicationHasGuidance:     $applicationHasGuidance,
            applicationHasRestrictions: $applicationHasRestrictions,
            applicationType:            $applicationType,
            attorneys:                  $attorneys,
            caseSubtype:                $caseSubtype,
            channel:                    $channel,
            dispatchDate:               $dispatchDate,
            donor:                      $donor,
            hasSeveranceWarning:        $hasSeveranceWarning,
            howAttorneysMakeDecisions:  $howAttorneysMakeDecisions,
            invalidDate:                $invalidDate,
            lifeSustainingTreatment:    $lifeSustainingTreatment,
            lpaDonorSignatureDate:      $lpaDonorSignatureDate,
            lpaIsCleansed:              $lpaIsCleansed,
            onlineLpaId:                $onlineLpaId,
            receiptDate:                $receiptDate,
            registrationDate:           $registrationDate,
            rejectedDate:               $rejectedDate,
            replacementAttorneys:       $replacementAttorneys,
            status:                     $status,
            statusDate:                 $statusDate,
            trustCorporations:          $trustCorporations,
            uId:                        $uId,
            withdrawnDate:              $withdrawnDate,
            whenTheLpaCanBeUsed:        $whenTheLpaCanBeUsed
        );
    }

    public static function makeLpaStoreLpa(
        ?bool $applicationHasGuidance = null,
        ?bool $applicationHasRestrictions = null,
        ?string $applicationType = null,
        ?array $attorneys = [],
        ?LpaType $caseSubtype = LpaType::PERSONAL_WELFARE,
        ?string $channel = 'online',
        ?DateTimeImmutable $dispatchDate = null,
        ?Person $donor = null,
        ?bool $hasSeveranceWarning = null,
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions = HowAttorneysMakeDecisions::SINGULAR,
        ?DateTimeImmutable $invalidDate = null,
        ?LifeSustainingTreatment $lifeSustainingTreatment = LifeSustainingTreatment::OPTION_A,
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::testDateString),
        ?bool $lpaIsCleansed = null,
        ?string $onlineLpaId = null,
        ?DateTimeImmutable $receiptDate = null,
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::testDateString),
        ?DateTimeImmutable $rejectedDate = null,
        ?array $replacementAttorneys = null,
        ?string $status = 'Registered',
        ?DateTimeImmutable $statusDate = null,
        ?array $trustCorporations = [],
        ?string $uId = 'uId',
        ?DateTimeImmutable $withdrawnDate = null,
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST,
    ): LpaStore {
        if (!is_null($attorneys) && count($attorneys) === 0) {
            $attorneys[] = EntityTestHelper::MakePerson();
        }

        if (!is_null($replacementAttorneys) && count($replacementAttorneys) === 0) {
            $replacementAttorneys[] = EntityTestHelper::MakePerson();
        }

        if (!is_null($trustCorporations) && count($trustCorporations) === 0) {
            $trustCorporations[] = EntityTestHelper::MakePerson();
        }

        return new LpaStore(
            applicationHasGuidance     : $applicationHasGuidance,
            applicationHasRestrictions : $applicationHasRestrictions,
            applicationType            : $applicationType,
            attorneys                  : $attorneys,
            caseSubtype                : $caseSubtype,
            channel                    : $channel,
            dispatchDate               : $dispatchDate,
            donor                      : $donor,
            hasSeveranceWarning        : $hasSeveranceWarning,
            howAttorneysMakeDecisions       : $howAttorneysMakeDecisions,
            invalidDate                : $invalidDate,
            lifeSustainingTreatment    : $lifeSustainingTreatment,
            lpaDonorSignatureDate      : $lpaDonorSignatureDate,
            lpaIsCleansed              : $lpaIsCleansed,
            onlineLpaId                : $onlineLpaId,
            receiptDate                : $receiptDate,
            registrationDate           : $registrationDate,
            rejectedDate               : $rejectedDate,
            replacementAttorneys       : $replacementAttorneys,
            status                     : $status,
            statusDate                 : $statusDate,
            trustCorporations          : $trustCorporations,
            uId                        : $uId,
            withdrawnDate              : $withdrawnDate,
            whenTheLpaCanBeUsed        : $whenTheLpaCanBeUsed,
        );
    }
}
