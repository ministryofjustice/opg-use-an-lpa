<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\ActorLpaCodesInterface;
use App\DataAccess\Repository\ViewerCodeActivityInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use DateTime;

class LpaServiceTest extends TestCase
{
    /**
     * @var ActorLpaCodesInterface
     */
    private $actorLpaCodesProphecy;

    /**
     * @var ViewerCodesInterface
     */
    private $viewerCodesProphecy;

    /**
     * @var ViewerCodeActivityInterface
     */
    private $viewerCodeActivityProphecy;

    public function setUp()
    {
        $this->actorLpaCodesProphecy = $this->prophesize(ActorLpaCodesInterface::class);
        $this->viewerCodesProphecy = $this->prophesize(ViewerCodesInterface::class);
        $this->viewerCodeActivityProphecy = $this->prophesize(ViewerCodeActivityInterface::class);
    }

    public function testGetById()
    {
        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $lpaId = '123456789012';

        $data = $service->getById($lpaId);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($lpaId, $data['id']);
    }

    public function testGetByIdNotFound()
    {
        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA not found');

        $service->getById('88888888888');
    }

    public function testGetByCode()
    {
        $shareCode = 'ABCDABCDABCD';
        $lpaId = '123456789012';

        $this->viewerCodesProphecy->get($shareCode)
            ->willReturn([
                'Expires'    => new DateTime('+1 hour'),
                'ViewerCode' => $shareCode,
                'SiriusId'   => $lpaId,
            ]);

        $this->viewerCodeActivityProphecy->recordSuccessfulLookupActivity($shareCode)
            ->shouldBeCalled();

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->getByCode($shareCode);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($lpaId, $data['id']);
    }

    public function testGetByCodeExpired()
    {
        $shareCode = 'Exp1r3d';

        $this->viewerCodesProphecy->get($shareCode)
            ->willReturn([
                'Expires'    => new DateTime(),
                'ViewerCode' => $shareCode,
                'SiriusId'   => '456',
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $this->expectException(GoneException::class);
        $this->expectExceptionMessage('Share code expired');

        $service->getByCode($shareCode);
    }

    public function testSearchDonorMatch()
    {
        $code = '123456789012';
        $uid = '123456789012';
        $dob = '1980-01-01';

        $this->actorLpaCodesProphecy->get($code)
            ->willReturn([
                'ActorLpaCode' => $uid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->search($code, $uid, $dob);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($uid, $data['id']);
    }

    public function testSearchAttorneyMatch()
    {
        $code = '123456789012';
        $uid = '123456789012';
        $dob = '1984-02-14';

        $this->actorLpaCodesProphecy->get($code)
            ->willReturn([
                'ActorLpaCode' => $uid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->search($code, $uid, $dob);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($uid, $data['id']);
    }

    public function testSearchCodeMismatch()
    {
        $code = '123456789012';
        $uid = '123456789012';
        $dob = '1980-01-01';

        $this->actorLpaCodesProphecy->get($code)
            ->willReturn([
                'ActorLpaCode' => 'm15match',
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No LPA found');

        $service->search($code, $uid, $dob);
    }

    public function testSearchDobMismatch()
    {
        $code = '123456789012';
        $uid = '123456789012';
        $dob = '1988-02-01';

        $this->actorLpaCodesProphecy->get($code)
            ->willReturn([
                'ActorLpaCode' => $uid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No LPA found');

        $service->search($code, $uid, $dob);
    }
}
