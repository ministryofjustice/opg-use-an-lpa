<?php

declare(strict_types=1);

namespace CommonTest\Helper;

use Common\Entity\CombinedLpa;
use Common\Entity\Person;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
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
            addressLine1: $line1,
            addressLine2: $line2,
            addressLine3: $line3,
            country:      $country,
            county:       $county,
            dob:          $dob,
            email:        $email,
            firstnames:   $firstnames,
            name:         $name,
            otherNames:   $otherNames,
            postcode:     $postcode,
            surname:      $surname,
            systemStatus: $systemStatus,
            town:         $town,
            uId:          $uId
        );
    }

    public static function makeCombinedLpa(
        ?bool $applicationHasGuidance = false,
        ?bool $applicationHasRestrictions = false,
        ?string $applicationType = 'Classic',
        ?array $attorneys = [],
        ?LpaType $caseSubtype = LpaType::PERSONAL_WELFARE,
        ?string $channel = 'channel',
        ?DateTimeImmutable $dispatchDate = null,
        ?Person $donor = null,
        ?bool $hasSeveranceWarning = null,
        ?HowAttorneysMakeDecisions $howAttorneysMakeDecisions = HowAttorneysMakeDecisions::SINGULAR,
        ?DateTimeImmutable $invalidDate = null,
        ?LifeSustainingTreatment $lifeSustainingTreatment = LifeSustainingTreatment::OPTION_A,
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?bool $lpaIsCleansed = true,
        ?string $onlineLpaId = 'onlineLpaId',
        ?DateTimeImmutable $receiptDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?DateTimeImmutable $rejectedDate = null,
        ?array $replacementAttorneys = [],
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
            whenTheLpaCanBeUsed:        $whenTheLpaCanBeUsed,
            withdrawnDate:              $withdrawnDate
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
        ?DateTimeImmutable $lpaDonorSignatureDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?bool $lpaIsCleansed = null,
        ?string $onlineLpaId = null,
        ?DateTimeImmutable $receiptDate = null,
        ?DateTimeImmutable $registrationDate = new DateTimeImmutable(TestData::TESTDATESTRING),
        ?DateTimeImmutable $rejectedDate = null,
        ?array $replacementAttorneys = null,
        ?string $status = 'Registered',
        ?DateTimeImmutable $statusDate = null,
        ?array $trustCorporations = [],
        ?string $uId = 'uId',
        ?DateTimeImmutable $withdrawnDate = null,
        ?WhenTheLpaCanBeUsed $whenTheLpaCanBeUsed = WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST,
    ): CombinedLpa {
        return new CombinedLpa(
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
            whenTheLpaCanBeUsed:        $whenTheLpaCanBeUsed,
            withdrawnDate:              $withdrawnDate
        );
    }
}
