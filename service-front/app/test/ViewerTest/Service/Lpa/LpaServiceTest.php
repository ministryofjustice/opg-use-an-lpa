<?php

declare(strict_types=1);

namespace ViewerTest\Service\Lpa;

use Viewer\Service\ApiClient\Client;
use PHPUnit\Framework\TestCase;
use Viewer\Service\Lpa\LpaService;
use ArrayObject;

class LpaServiceTest extends TestCase
{
    public function testConstructor()
    {
        $apiClientProphecy = $this->prophesize(Client::class);

        $service = new LpaService($apiClientProphecy->reveal());

        $this->assertInstanceOf(LpaService::class, $service);
    }

    public function testGetLpa()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/v1/lpa-by-code/123456789012')
            ->willReturn([
                'id'      => 12345678901,
                'isValid' => true,
            ]);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpaByCode('1234-5678-9012');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(12345678901, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

    public function testGetLpaNotFound()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/v1/lpa-by-code/123412341234')
            ->willReturn(null);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpaByCode('1234-1234-1234');

        $this->assertNotInstanceOf(ArrayObject::class, $lpa);
        $this->assertNull($lpa);
    }

    public function testGetLpaById()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/v1/lpa/12345678901')
            ->willReturn([
                'id'      => 12345678901,
                'isValid' => true,
            ]);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpaById(12345678901);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(12345678901, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

}
