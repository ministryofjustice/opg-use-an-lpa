<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use App\Exception\LpaAlreadyAddedException;
use App\Exception\LpaNotRegisteredException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\ValidatedActorCode;
use App\Service\Lpa\AddLpa\AddLpa;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AddLpaTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    private ActorCodeService|ObjectProphecy $actorCodeServiceProphecy;

    private LpaAlreadyAdded|ObjectProphecy $lpaAlreadyAddedProphecy;

    private string $userId;

    private string $actorCode;

    private string $lpaUid;

    private string $dob;

    protected function setUp(): void
    {
        $this->loggerProphecy           = $this->prophesize(LoggerInterface::class);
        $this->actorCodeServiceProphecy = $this->prophesize(ActorCodeService::class);
        $this->lpaAlreadyAddedProphecy  = $this->prophesize(LpaAlreadyAdded::class);

        $this->actorCode = '4UAL33PEQNAY';
        $this->userId    = '12345';
        $this->lpaUid    = '700000004321';
        $this->dob       = '1975-10-05';
    }

    private function addLpa(): AddLpa
    {
        return new AddLpa(
            $this->loggerProphecy->reveal(),
            $this->actorCodeServiceProphecy->reveal(),
            $this->lpaAlreadyAddedProphecy->reveal()
        );
    }

    #[Test]
    public function it_throws_a_bad_request_if_lpa_already_added(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(['lpa added data']);

        try {
            $this->addLpa()->validateAddLpaData(
                [
                'actor-code' => $this->actorCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->dob,
                ],
                $this->userId
            );
        } catch (LpaAlreadyAddedException $lpaAlreadyAddedException) {
            $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $lpaAlreadyAddedException->getCode());
            $this->assertSame('LPA already added', $lpaAlreadyAddedException->getMessage());
            $this->assertSame(['lpa added data'], $lpaAlreadyAddedException->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('A bad request exception should have been thrown');
    }

    #[Test]
    public function it_accepts_lpas_that_have_been_requested_to_be_added_but_not_activated(): void
    {
        $expectedResponse = new ValidatedActorCode(
            $this->prophesize(LpaActor::class)->reveal(),
            $this->oldLpaFixture(),
            true,
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(
                [
                    'lpaAddedData' => 'data',
                    'notActivated' => true,
                ]
            );

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn($expectedResponse);

        $lpaData = $this->addLpa()->validateAddLpaData(
            [
                'actor-code' => $this->actorCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->dob,
            ],
            $this->userId
        );

        $this->assertEquals($expectedResponse, $lpaData);
    }

    #[Test]
    public function it_accepts_lpas_that_have_been_requested_to_be_added_but_not_activated_combined_format(): void
    {
        $expectedResponse = new ValidatedActorCode(
            $this->prophesize(LpaActor::class)->reveal(),
            $this->newLpaFixture('Registered'),
            false,
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(
                [
                    'lpaAddedData' => 'data',
                    'notActivated' => true,
                ]
            );

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn($expectedResponse);

        $lpaData = $this->addLpa()->validateAddLpaData(
            [
                'actor-code' => $this->actorCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->dob,
            ],
            $this->userId
        );

        $this->assertEquals($expectedResponse, $lpaData);
    }

    #[Test]
    public function it_throws_a_bad_request_if_code_validation_fails(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn(null);

        try {
            $this->addLpa()->validateAddLpaData(
                [
                    'actor-code' => $this->actorCode,
                    'uid'        => $this->lpaUid,
                    'dob'        => $this->dob,
                ],
                $this->userId
            );
        } catch (NotFoundException $notFoundException) {
            $this->assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $notFoundException->getCode());
            $this->assertSame('Code validation failed', $notFoundException->getMessage());
            return;
        }

        throw new ExpectationFailedException('A not found exception should have been thrown');
    }

    #[Test]
    public function it_throws_a_bad_request_if_the_lpa_status_is_not_registered(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn(
                new ValidatedActorCode(
                    $this->prophesize(LpaActor::class)->reveal(),
                    new SiriusLpa(
                        [
                            'status' => 'Cancelled',
                        ],
                        $this->loggerProphecy->reveal(),
                    ),
                    false,
                ),
            );

        $this->expectException(LpaNotRegisteredException::class);
        $this->addLpa()->validateAddLpaData(
            [
                'actor-code' => $this->actorCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->dob,
            ],
            $this->userId
        );
    }

    #[Test]
    public function it_throws_a_bad_request_if_the_lpa_status_is_not_registered_combined_format(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn(
                new ValidatedActorCode(
                    $this->prophesize(LpaActor::class)->reveal(),
                    $this->newLpaFixture('Cancelled'),
                    false,
                ),
            );

        $this->expectException(LpaNotRegisteredException::class);
        $this->addLpa()->validateAddLpaData(
            [
                'actor-code' => $this->actorCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->dob,
            ],
            $this->userId
        );
    }

    #[Test]
    public function it_returns_the_lpa_data_if_all_validation_checks_passed(): void
    {
        $expectedResponse = new ValidatedActorCode(
            $this->prophesize(LpaActor::class)->reveal(),
            $this->oldLpaFixture(),
            false,
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn($expectedResponse);

        $lpaData = $this->addLpa()->validateAddLpaData(
            [
                    'actor-code' => $this->actorCode,
                    'uid'        => $this->lpaUid,
                    'dob'        => $this->dob,
            ],
            $this->userId
        );

        $this->assertEquals($expectedResponse, $lpaData);
    }

    public function oldLpaFixture(): SiriusLpa
    {
        return new SiriusLpa(
            [
                'status' => 'Registered',
            ],
            $this->loggerProphecy->reveal(),
        );
    }

    public function newLpaFixture($status): CombinedSiriusLpa
    {
        return new CombinedSiriusLpa(
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
            registrationDate:                          null,
            rejectedDate:                              null,
            replacementAttorneys:                      null,
            status:                                    $status,
            statusDate:                                null,
            trustCorporations:                         null,
            uId:                                       '700000012346',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );
    }
}
