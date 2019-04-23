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
        $apiClientProphecy->httpGet('/path/to/lpa', [
                'code' => 123,
            ])
            ->willReturn([
                'id'      => 987654321,
                'isValid' => true,
            ]);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpa('123');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(987654321, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

    public function testGetLpaNotFound()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/path/to/lpa', [
                'code' => 999,
            ])
            ->willReturn(null);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpa('999');

        $this->assertNotInstanceOf(ArrayObject::class, $lpa);
        $this->assertNull($lpa);
    }

    public function testGetLpaById()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/path/to/lpa', [
                'id' => 123456789,
            ])
            ->willReturn([
                'id'      => 123456789,
                'isValid' => true,
            ]);

        $service = new LpaService($apiClientProphecy->reveal());

        $lpa = $service->getLpaById(123456789);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertEquals(123456789, $lpa->id);
        $this->assertEquals(true, $lpa->isValid);
    }

}
