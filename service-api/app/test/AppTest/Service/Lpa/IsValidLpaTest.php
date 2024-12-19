<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\IsValidLpa;
use App\Service\Lpa\SiriusLpa;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class IsValidLpaTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy     = $this->prophesize(LoggerInterface::class);
        $this->isValidLpaProphecy = $this->prophesize(IsValidLpa::class);
    }

    private function isValidLpaResolver(): IsValidLpa
    {
        return new IsValidLpa(
            $this->loggerProphecy->reveal()
        );
    }

    #[Test]
    public function check_if_lpa_valid_when_status_registered(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'    => '700000000001',
                'status' => 'Registered',
                'donor'  => [
                    'id' => 1,
                ],
            ],
            $this->loggerProphecy->reveal(),
        );

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_cancelled(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'    => '700000000001',
                'status' => 'Cancelled',
                'donor'  => [
                    'id' => 1,
                ],
            ],
            $this->loggerProphecy->reveal(),
        );

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_other_than_registered_or_cancelled(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'    => '700000000001',
                'status' => 'Revoked',
                'donor'  => [
                    'id' => 1,
                ],
            ],
            $this->loggerProphecy->reveal(),
        );

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertFalse($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_registered_combined_format(): void
    {
        $lpa = new \App\Entity\Sirius\SiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           false,
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
            status:                                    'Registered',
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '700000000001',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_cancelled_combined_format(): void
    {
        $lpa = new \App\Entity\Sirius\SiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           false,
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
            status:                                    'Cancelled',
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '700000000001',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_other_than_registered_or_cancelled_combined_format(): void
    {
        $lpa = new \App\Entity\Sirius\SiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           false,
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
            status:                                    "Revoked",
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '700000000001',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertFalse($result);
    }
}
