<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use App\Service\Lpa\RemoveLpa;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveLpaTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var UserLpaActorMapInterface
     */
    private $userLpaActorMapInterfaceProphecy;

    /**
     * @var ViewerCodesInterface
     */
    private $viewerCodesInterfaceProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->viewerCodesInterfaceProphecy = $this->prophesize(ViewerCodesInterface::class);
    }

    private function deleteLpa(): RemoveLpa
    {
        return new RemoveLpa(
            $this->loggerProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->viewerCodesInterfaceProphecy->reveal()
        );
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_successfully()
    {
        $userActorLpa = [
            'SiriusUid' => '700000055554',
            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
            'Id' => '2345Token0123',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $viewerCodes = [
            'Id'            => '1',
            'ViewerCode'    => '123ABCD6789',
            'SiriusUid'     => '700000055554',
            'Added'         => '2020-01-01 00:00:00',
            'Expires'       => '2021-02-01 00:00:00',
            'UserLpaActor' => '2345Token0123',
            'Organisation' => 'Some Organisation',
        ];

        $removedresponse = [
            'Id' => '1',
            'SiriusUid' => '700000055554',
            'Added' => '2020-01-01 00:00:00',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy->getCodesByLpaId($userActorLpa['SiriusUid'])->willReturn($viewerCodes);
        $this->viewerCodesInterfaceProphecy->removeActorAssociation($viewerCodes['ViewerCode'])->willReturn(true);
        $this->userLpaActorMapInterfaceProphecy->delete('2345Token0123')->willReturn($removedresponse);

        $deleteLpa = $this->deleteLpa();
        $result = ($deleteLpa)('1234', '2345Token0123');

        $this->assertNotEmpty($result);
        $this->assertEquals($result['SiriusUid'], $userActorLpa['SiriusUid']);
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_no_viewer_codes_to_update()
    {
        $userActorLpa = [
            'SiriusUid' => '700000055554',
            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
            'Id' => '2345Token0123',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $viewerCodes = [];

        $removedresponse = [
            'Id' => '1',
            'SiriusUid' => '700000055554',
            'Added' => '2020-01-01 00:00:00',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy->getCodesByLpaId($userActorLpa['SiriusUid'])->willReturn($viewerCodes);
        $this->viewerCodesInterfaceProphecy->removeActorAssociation($viewerCodes['ViewerCode'])->willReturn(true);
        $this->userLpaActorMapInterfaceProphecy->delete('2345Token0123')->willReturn($removedresponse);

        $deleteLpa = $this->deleteLpa();
        $result = ($deleteLpa)('1234', '2345Token0123');

        $this->assertNotEmpty($result);
        $this->assertEquals($result['SiriusUid'], $userActorLpa['SiriusUid']);
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_actor_token_not_found()
    {
        $userActorLpa = null;
        $viewerCodes = null;

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);

        $deleteLpa = $this->deleteLpa();
        ($deleteLpa)('1234', '2345Token0123');
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_user_id_does_not_match()
    {
        $userActorLpa = [
            'SiriusUid' => '700000055554',
            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
            'Id' => '2345Token0123',
            'ActorId' => '1',
            'UserId' => '6789',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();


        $this->expectException(NotFoundException::class);

        $deleteLpa = $this->deleteLpa();
        ($deleteLpa)('1234', '2345Token0123');
    }
}
