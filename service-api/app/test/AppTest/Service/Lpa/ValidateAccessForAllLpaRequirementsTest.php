<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\ValidateAccessForAllLpaRequirements;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use DateTimeImmutable;

#[CoversClass(ValidateAccessForAllLpaRequirements::class)]
class ValidateAccessForAllLpaRequirementsTest extends TestCase
{
    private LoggerInterface|MockObject $mockLogger;

    public function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function validateLpaRequirements(): ValidateAccessForAllLpaRequirements
    {
        return new ValidateAccessForAllLpaRequirements(
            $this->mockLogger,
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function throws_bad_request_exception_when_lpa_status_is_not_registered(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'    => '123456789012',
                'status' => 'Pending',
            ],
            $this->mockLogger,
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function when_allow_older_lpa_flag_on_throws_exception_when_status_is_not_registered(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'              => '123456789012',
                'status'           => 'Pending',
                'registrationDate' => '2019-08-31',
            ],
            $this->mockLogger,
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function when_allow_older_lpa_flag_on_throws_no_exception_when_status_is_registered(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'              => '123456789012',
                'status'           => 'Registered',
                'registrationDate' => '2019-08-31',
            ],
            $this->mockLogger,
        );

        $response = $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
        $this->assertNull($response);
    }

    #[Test]
    public function throws_bad_request_exception_when_lpa_status_is_not_registered_combined_format(): void
    {
        $lpa = new CombinedSiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       false,
            caseAttorneyJointlyAndJointlyAndSeverally: null,
            caseAttorneyJointlyAndSeverally:           true,
            howAttorneysMakeDecisionsDetails:          null,
            caseSubtype:                               null,
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     null,
            hasSeveranceWarning:                       null,
            invalidDate:                               null,
            lifeSustainingTreatment:                   null,
            lpaDonorSignatureDate:                     null,
            lpaIsCleansed:                             null,
            onlineLpaId:                               null,
            receiptDate:                               null,
            registrationDate:                          null,
            rejectedDate:                              null,
            replacementAttorneys:                      null,
            status:                                    'Pending',
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '123456789012',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );


        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function when_allow_older_lpa_flag_on_throws_exception_when_status_is_not_registered_combined_format(): void
    {
        $lpa = new CombinedSiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       false,
            caseAttorneyJointlyAndJointlyAndSeverally: null,
            caseAttorneyJointlyAndSeverally:           true,
            howAttorneysMakeDecisionsDetails:          null,
            caseSubtype:                               null,
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     null,
            hasSeveranceWarning:                       null,
            invalidDate:                               null,
            lifeSustainingTreatment:                   null,
            lpaDonorSignatureDate:                     null,
            lpaIsCleansed:                             null,
            onlineLpaId:                               null,
            receiptDate:                               null,
            registrationDate:                          new DateTimeImmutable('2019-08-31'),
            rejectedDate:                              null,
            replacementAttorneys:                      null,
            status:                                    'Pending',
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '123456789012',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
    }

    #[Test]
    public function when_allow_older_lpa_flag_on_throws_no_exception_when_status_is_registered_combined_format(): void
    {
        $lpa = new CombinedSiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       false,
            caseAttorneyJointlyAndJointlyAndSeverally: null,
            caseAttorneyJointlyAndSeverally:           true,
            howAttorneysMakeDecisionsDetails:          null,
            caseSubtype:                               null,
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     null,
            hasSeveranceWarning:                       null,
            invalidDate:                               null,
            lifeSustainingTreatment:                   null,
            lpaDonorSignatureDate:                     null,
            lpaIsCleansed:                             null,
            onlineLpaId:                               null,
            receiptDate:                               null,
            registrationDate:                          new DateTimeImmutable('2019-08-31'),
            rejectedDate:                              null,
            replacementAttorneys:                      null,
            status:                                    'Registered',
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '123456789012',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $response = $this->validateLpaRequirements()($lpa->getUid(), $lpa->getStatus());
        $this->assertNull($response);
    }
}
