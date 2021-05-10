<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\RemoveLpa;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class RemoveLpaTest extends TestCase
{
    private string $actorLpaToken;
    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;
    private Lpa $lpa;
    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;
    private string $lpaUid;
    private array $removedData;
    private array $userActorLpa;
    private string $userId;
    /** @var ObjectProphecy|UserLpaActorMapInterface */
    private $userLpaActorMapInterfaceProphecy;
    private array $viewerCodes;
    /** @var ObjectProphecy|ViewerCodesInterface */
    private $viewerCodesInterfaceProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->viewerCodesInterfaceProphecy = $this->prophesize(ViewerCodesInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        $this->lpaUid = '700000055554';
        $this->actorLpaToken = '2345Token0123';
        $this->userId = '1234-0000-1234-0000';
        $this->lpa = new Lpa(
            [
                'uId' => $this->lpaUid,
                'other' => 'data',
            ],
            new DateTime()
        );

        $this->userActorLpa = [
            'SiriusUid' => $this->lpaUid,
            'Added' => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'Id' => $this->actorLpaToken,
            'ActorId' => '1',
            'UserId' => $this->userId,
        ];

        $this->viewerCodes = [
            0 => [ // this code is active
                'Id' => '1',
                'ViewerCode' => '123ABCD6789R',
                'SiriusUid' => $this->lpaUid,
                'Added' => (new DateTime())->format('Y-m-d'),
                'Expires' => (new DateTime())->modify('+1 month')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation',
            ],
            1 => [ // this code has expired
                'Id' => '2',
                'ViewerCode' => 'YG41BCD693FH',
                'SiriusUid' => $this->lpaUid,
                'Added' => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                'Expires' => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation 2',
            ],
            2 => [ // this code is already cancelled
                'Id' => '3',
                'ViewerCode' => 'RL2AD1936KV2',
                'SiriusUid' => $this->lpaUid,
                'Added' => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                'Expires' => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                'Cancelled' => (new DateTime())->modify('-2 months')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation 3',
            ],
        ];

        $this->removedData = [
            'Id' => '1',
            'SiriusUid' => $this->lpaUid,
            'Added' => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'ActorId' => '1',
            'UserId' => $this->userId,
        ];
    }

    /** @test */
    public function it_can_remove_lpa_from_a_user_account_with_no_viewer_codes_to_update()
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($this->userActorLpa['SiriusUid'])
            ->willReturn([]);

        $this->lpaServiceProphecy
            ->getByUid($this->userActorLpa['SiriusUid'])
            ->willReturn($this->lpa);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->removedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($this->lpa->getData(), $result);
    }

    /** @test */
    public function it_removes_an_lpa_from_a_user_account_and_cancels_their_active_codes_only()
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($this->userActorLpa['SiriusUid'])
            ->willReturn($this->viewerCodes);

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[0]['ViewerCode'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[0]['ViewerCode'], Argument::type('Datetime'))
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[1]['ViewerCode'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[1]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[2]['ViewerCode'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[2]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->lpaServiceProphecy
            ->getByUid($this->userActorLpa['SiriusUid'])
            ->willReturn($this->lpa);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->removedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($this->lpa->getData(), $result);
    }

    /** @test */
    public function it_throws_exception_if_actor_lpa_token_not_found()
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn(null)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('User actor lpa record not found for actor token - ' . $this->actorLpaToken);

        ($this->deleteLpa())($this->userId, $this->actorLpaToken);
    }

    /** @test */
    public function it_throws_exception_if_user_id_does_not_match_actor_lpa_data()
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage(
            'User Id passed does not match the user in userActorLpaMap for token - ' .
            $this->actorLpaToken
        );
        ($this->deleteLpa())('wR0ng1D', $this->actorLpaToken);
    }

    private function deleteLpa(): RemoveLpa
    {
        return new RemoveLpa(
            $this->loggerProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->lpaServiceProphecy->reveal()
        );
    }
}
