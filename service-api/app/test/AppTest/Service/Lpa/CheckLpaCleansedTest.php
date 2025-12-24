<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\Exception\LpaNeedsCleansingException;
use App\Service\Lpa\CheckLpaCleansed;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusLpaManager;
use App\Service\Lpa\SiriusPerson;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(CheckLpaCleansed::class)]
class CheckLpaCleansedTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private SiriusLpaManager|ObjectProphecy $lpaServiceProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy     = $this->prophesize(LoggerInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(SiriusLpaManager::class);
    }

    public function checkLpaCleansed(): CheckLpaCleansed
    {
        return new CheckLpaCleansed(
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    #[Test]
    public function older_lpa_add_confirmation_throws_an_exception_if_lpa_not_cleansed_and_registered_before_sep2019(): void
    {
        $userId = '1234';
        $lpa    = $this->oldLpaFixture(false, '2018-05-26');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $this->expectException(LpaNeedsCleansingException::class);
        $this->expectExceptionMessage('LPA needs cleansing');

        $this->checkLpaCleansed()($userId, $actorDetailsMatch);
    }

    #[Test]
    public function older_lpa_add_confirmation_throws_an_exception_if_lpa_not_cleansed_and_registered_before_sep2019_combined_format(): void
    {
        $userId = '1234';
        $lpa    = $this->newSiriusFixture(false, '2018-05-26');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $this->expectException(LpaNeedsCleansingException::class);
        $this->expectExceptionMessage('LPA needs cleansing');

        $this->checkLpaCleansed()($userId, $actorDetailsMatch);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_cleansed_lpa_and_registered_before_sep2019(): void
    {
        $userId = '1234';
        $lpa    = $this->oldLpaFixture(true, '2018-05-26');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_cleansed_lpa_and_registered_before_sep2019_combined_format(): void
    {
        $userId = '1234';
        $lpa    = $this->newSiriusFixture(true, '2018-05-26');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_lpa_not_cleansed_and_registered_after_sep2019(): void
    {
        $userId = '1234';
        $lpa    = $this->oldLpaFixture(false, '2019-09-01');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_lpa_not_cleansed_and_registered_after_sep2019_combined_format(): void
    {
        $userId = '1234';
        $lpa    = $this->newSiriusFixture(false, '2019-09-01');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_an_lpa_cleansed_and_registered_after_sep2019(): void
    {
        $userId = '1234';
        $lpa    = $this->oldLpaFixture(true, '2019-09-01');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_an_lpa_cleansed_and_registered_after_sep2019_combined_format(): void
    {
        $userId = '1234';
        $lpa    = $this->newSiriusFixture(true, '2019-09-01');

        $actorDetailsMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId' => '700000000002',
                ],
                $this->loggerProphecy->reveal(),
            ),
            '',
            '700000000001',
        );

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch->lpaUId)
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    public function oldLpaFixture(bool $lpaIsCleansed, string $registrationDate)
    {
        return new Lpa(
            new SiriusLpa(
                [
                    'registrationDate' => $registrationDate,
                    'lpaIsCleansed'    => $lpaIsCleansed,
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );
    }

    public function newSiriusFixture(bool $lpaIsCleansed, string $registrationDate)
    {
        return new Lpa(
            new CombinedSiriusLpa(
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
                lpaIsCleansed:                             $lpaIsCleansed,
                onlineLpaId:                               null,
                receiptDate:                               null,
                registrationDate:                          new DateTimeImmutable($registrationDate),
                rejectedDate:                              null,
                replacementAttorneys:                      null,
                status:                                    null,
                statusDate:                                null,
                trustCorporations:                         null,
                uId:                                       null,
                whenTheLpaCanBeUsed:                       null,
                withdrawnDate:                             null
            ),
            new DateTime()
        );
    }
}
