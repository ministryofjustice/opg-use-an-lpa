<?php

namespace AppTest\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Lpa\AddLpa;
use App\Service\Lpa\LpaAlreadyAdded;
use Fig\Http\Message\StatusCodeInterface;
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

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodeServiceProphecy = $this->prophesize(ActorCodeService::class);
        $this->lpaAlreadyAddedProphecy = $this->prophesize(LpaAlreadyAdded::class);

        $this->actorCode = '4UAL33PEQNAY';
        $this->userId = '12345';
        $this->lpaUid = '700000004321';
        $this->dob = '1975-10-05';
    }

    private function addLpa(): AddLpa
    {
        return new AddLpa(
            $this->loggerProphecy->reveal(),
            $this->actorCodeServiceProphecy->reveal(),
            $this->lpaAlreadyAddedProphecy->reveal()
        );
    }

    /** @test */
    public function it_throws_a_bad_request_if_lpa_already_added(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(['lpa added data']);

        try {
            $this->addLpa()->validateAddLpaData(
                [
                'actor-code' => $this->actorCode,
                'uid' => $this->lpaUid,
                'dob' => $this->dob
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            $this->assertEquals('LPA already added', $ex->getMessage());
            $this->assertEquals(['lpa added data'], $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('A bad request exception should have been thrown');
    }

    /** @test */
    public function it_accepts_lpas_that_have_been_requested_to_be_added_but_not_activated(): void
    {
        $expectedResponse = [
            'some' => 'other data',
            'lpa' => [
                'status' => 'Registered'
            ]
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(
                [
                    'lpaAddedData' => 'data',
                    'notActivated' => true
                ]
            );

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn($expectedResponse);

        $lpaData = $this->addLpa()->validateAddLpaData(
            [
                'actor-code' => $this->actorCode,
                'uid' => $this->lpaUid,
                'dob' => $this->dob
            ],
            $this->userId
        );

        $this->assertEquals($expectedResponse, $lpaData);
    }

    /** @test */
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
                    'uid' => $this->lpaUid,
                    'dob' => $this->dob
                ],
                $this->userId
            );
        } catch (NotFoundException $ex) {
            $this->assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            $this->assertEquals('Code validation failed', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('A not found exception should have been thrown');
    }

    /** @test */
    public function it_throws_a_bad_request_if_the_lpa_status_is_not_registered(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn(
                [
                    'lpa' => [
                        'status' => 'Cancelled'
                    ]
                ]
            );

        try {
            $this->addLpa()->validateAddLpaData(
                [
                    'actor-code' => $this->actorCode,
                    'uid' => $this->lpaUid,
                    'dob' => $this->dob
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            $this->assertEquals('LPA status is not registered', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('A bad request exception should have been thrown');
    }

    /** @test */
    public function it_returns_the_lpa_data_if_all_validation_checks_passed(): void
    {
        $expectedResponse = [
            'some' => 'other data',
            'lpa' => [
                'status' => 'Registered'
            ]
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, $this->lpaUid)
            ->willReturn(null);

        $this->actorCodeServiceProphecy
            ->validateDetails($this->actorCode, $this->lpaUid, $this->dob)
            ->willReturn($expectedResponse);

        $lpaData = $this->addLpa()->validateAddLpaData(
            [
                    'actor-code' => $this->actorCode,
                    'uid' => $this->lpaUid,
                    'dob' => $this->dob
            ],
            $this->userId
        );

        $this->assertEquals($expectedResponse, $lpaData);
    }
}
