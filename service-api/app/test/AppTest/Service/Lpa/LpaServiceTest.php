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

    /**
     * VALID VALUES AS PER THE SAMPLE DATA IN THE lpas-gateway.json file
     * TODO - Move this data into a mock when a client for the Gateway is injected into the LpaService
     */
    private $validLpaId = '700000000047';
    private $validActorDonorCode = '100000000070';
    private $validActorDonorUid = '700000000070';
    private $validActorDonorDob = '1948-11-01';
    private $validActorAttorneyCode = '100000000096';
    private $validActorAttorneyUid = '700000000096';
    private $validActorAttorneyDob = '1975-10-05';

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

        $data = $service->getById($this->validLpaId);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('uId', $data);
        $this->assertEquals($this->validLpaId, str_replace('-', '', $data['uId']));
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

        $this->viewerCodesProphecy->get($shareCode)
            ->willReturn([
                'Expires'    => new DateTime('+1 hour'),
                'ViewerCode' => $shareCode,
                'SiriusUid'  => $this->validLpaId,
            ]);

        $this->viewerCodeActivityProphecy->recordSuccessfulLookupActivity($shareCode)
            ->shouldBeCalled();

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->getByCode($shareCode);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('uId', $data);
        $this->assertEquals($this->validLpaId, str_replace('-', '', $data['uId']));
    }

    public function testGetByCodeExpired()
    {
        $shareCode = 'Exp1r3d';

        $this->viewerCodesProphecy->get($shareCode)
            ->willReturn([
                'Expires'    => new DateTime(),
                'ViewerCode' => $shareCode,
                'SiriusUid'  => '456',
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
        $this->actorLpaCodesProphecy->get($this->validActorDonorCode)
            ->willReturn([
                'ActorLpaCode'   => $this->validActorDonorCode,
                'SiriusUid'      => $this->validLpaId,
                'ActorSiriusUid' => $this->validActorDonorUid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->search($this->validActorDonorCode, $this->validLpaId, $this->validActorDonorDob);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('uId', $data);
        $this->assertEquals($this->validLpaId, str_replace('-', '', $data['uId']));
    }

    public function testSearchAttorneyMatch()
    {
        $this->actorLpaCodesProphecy->get($this->validActorAttorneyCode)
            ->willReturn([
                'ActorLpaCode'   => $this->validActorAttorneyCode,
                'SiriusUid'      => $this->validLpaId,
                'ActorSiriusUid' => $this->validActorAttorneyUid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $data = $service->search($this->validActorAttorneyCode, $this->validLpaId, $this->validActorAttorneyDob);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('uId', $data);
        $this->assertEquals($this->validLpaId, str_replace('-', '', $data['uId']));
    }

    public function testSearchCodeMismatch()
    {
        $code = '123456789012';
        $uid = '123456789012';
        $dob = '1980-01-01';

        $this->actorLpaCodesProphecy->get($code)
            ->willReturn([
                'ActorLpaCode'   => 'm15match',
                'SiriusUid'      => 'm15match',
                'ActorSiriusUid' => 'm15match',
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
        $dob = '2000-02-01';

        $this->actorLpaCodesProphecy->get($this->validActorDonorCode)
            ->willReturn([
                'ActorLpaCode'   => $this->validActorDonorCode,
                'SiriusUid'      => $this->validLpaId,
                'ActorSiriusUid' => $this->validActorDonorUid,
            ]);

        $service = new LpaService($this->viewerCodesProphecy->reveal(),
            $this->viewerCodeActivityProphecy->reveal(),
            $this->actorLpaCodesProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No LPA found');

        $service->search($this->validActorDonorCode, $this->validLpaId, $dob);
    }
}
