<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use DateTime;

class LpaServiceTest extends TestCase
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesInterfaceProphecy;

    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodeActivityInterfaceProphecy;

    /**
     * @var Repository\ViewerCodesInterface
     */
    private $lpasInterfaceProphecy;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapInterfaceProphecy;


    public function setUp()
    {
        $this->viewerCodesInterfaceProphecy = $this->prophesize(Repository\ViewerCodesInterface::class);
        $this->viewerCodeActivityInterfaceProphecy = $this->prophesize(Repository\ViewerCodeActivityInterface::class);
        $this->lpasInterfaceProphecy = $this->prophesize(Repository\LpasInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
    }

    private function getLpaService() : LpaService
    {
        return new LpaService(
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->viewerCodeActivityInterfaceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
        );
    }

    public function testGetById()
    {
        $testUid = '700012349874';
        $mockLpaResponse = $this->prophesize(Repository\Response\LpaInterface::class)->reveal();

        //---

        $service = $this->getLpaService();

        $this->lpasInterfaceProphecy->get($testUid)->willReturn($mockLpaResponse);

        $result = $service->getByUid($testUid);

        //---

        // We simply expect the $mockLpaResponse to be returned, unchanged.
        $this->assertEquals($mockLpaResponse, $result);
    }

}
