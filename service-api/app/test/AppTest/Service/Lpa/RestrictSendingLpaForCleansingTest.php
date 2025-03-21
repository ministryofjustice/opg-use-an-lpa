<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Exception\NotFoundException;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use App\Service\Lpa\SiriusLpa;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;

#[CoversClass(RestrictSendingLpaForCleansing::class)]
class RestrictSendingLpaForCleansingTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    public function restrictSendingLpaForCleansing(): RestrictSendingLpaForCleansing
    {
        return new RestrictSendingLpaForCleansing(
            $this->loggerProphecy->reveal(),
        );
    }

    #[Test]
    public function throws_not_found_exception_when_lpa_status_registered_and_actorMatch_is_null(): void
    {
        $lpa = new SiriusLpa(
            [
                'uId'              => '123456789012',
                'registrationDate' => '2020-05-26',
            ],
            $this->loggerProphecy->reveal(),
        );

        $actorDetailsMatch = null;

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA not found');

        $this->restrictSendingLpaForCleansing()($lpa, $actorDetailsMatch);
    }

    #[Test]
    public function throws_not_found_exception_when_lpa_status_registered_and_actorMatch_is_null_combined_format(): void
    {
        $lpa = new CombinedSiriusLpa(
            applicationHasGuidance:                    null,
            applicationHasRestrictions:                null,
            applicationType:                           null,
            attorneys:                                 null,
            caseAttorneyJointly:                       false,
            caseAttorneyJointlyAndJointlyAndSeverally: null,
            caseAttorneyJointlyAndSeverally:           true,
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
            registrationDate:                          new DateTimeImmutable('2020-05-26'),
            rejectedDate:                              null,
            replacementAttorneys:                      null,
            status:                                    null,
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '123456789012',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );

        $actorDetailsMatch = null;

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA not found');

        $this->restrictSendingLpaForCleansing()($lpa, $actorDetailsMatch);
    }
}
