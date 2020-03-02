<?php

declare(strict_types=1);

namespace AppTest\Service\ViewerCodes;

use PHPUnit\Framework\TestCase;
use App\Service\ViewerCodes\ViewerCodeService;
use App\DataAccess\Repository;
use App\Service\Lpa\LpaService;
use DateTime;

class CodeCancellationTest extends TestCase
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesInterfaceProphecy;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapInterfaceProphecy;

    /**
     * @var LpaService
     */
    private $lpaServiceProphecy;

    public function setUp()
    {
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->viewerCodesInterfaceProphecy = $this->prophesize(Repository\ViewerCodesInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
    }

    private function getViewerCodeService(): ViewerCodeService
    {
        return new ViewerCodeService(
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
        );
    }

    /** @test */
    public function cancel_code()
    {
        $shareCode = [
            'SiriusUid'        => '700000000054',
            'Added'            => '2021-01-05 12:34:56',
            'Expires'          => '2022-01-05 12:34:56',
            'UserLpaActor'     => '111222333444',
            'Organisation'     => 'TestOrg',
            'ViewerCode'       => 'XYZ321ABC987',
        ];

        $t = new \StdClass();

        $t->Code = 'XYZ321ABC987';
        $t->Token = 'test-token';
        $t->UserId = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId = 1;
        $t->Lpa = [];

        $this->userLpaActorMapInterfaceProphecy->get($t->Token)->willReturn([
            'Id' => $t->Token,
            'UserId' => $t->SiriusUid,
            'SiriusUid' => $t->SiriusUid,
            'ActorId' => $t->ActorId,
        ]);

        $this->viewerCodesInterfaceProphecy->get($t->Code)->willReturn($shareCode);

        $service = $this->getViewerCodeService();
        $result  = $service->cancelCode($t->Token,$t->Code,'26/02/2020');

        $this->assertEquals(null,$result);
    }
}
