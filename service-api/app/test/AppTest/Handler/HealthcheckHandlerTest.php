<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\ActorCodesInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Handler\HealthcheckHandler;
use Zend\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    private $actorCodesProphecy;

    private $lpaInterface;

    protected function setUp()
    {
        $this->actorCodesProphecy = $this->prophesize(ActorCodesInterface::class);
        $this->lpaInterface = $this->prophesize(LpasInterface::class);
    }
    
    public function testReturnsExpectedJsonResponse()
    {
        $version = 'dev';

        $this->lpaInterface->get("700000000000")
            ->willReturn(null);

        $this->actorCodesProphecy->get('XXXXXXXXXXXX')
            ->willReturn(null);

        $healthcheck = new HealthcheckHandler($version, $this->lpaInterface->reveal(), $this->actorCodesProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $healthcheck->handle($requestProphecy->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('healthy', $json);
        $this->assertArrayHasKey('version', $json);
        $this->assertArrayHasKey('dependencies', $json);

        $dependencies = $json['dependencies'];
        $this->assertArrayHasKey('api', $dependencies);
        $this->assertArrayHasKey('dynamo', $dependencies);

        $api = $dependencies['api'];
        $dynamo = $dependencies['dynamo'];
        $this->assertArrayHasKey('healthy', $api);
        $this->assertArrayHasKey('healthy', $dynamo);

        $this->assertEquals(true, $api['healthy']);
        $this->assertEquals(true, $dynamo['healthy']);
        $this->assertEquals(true, $json['healthy']);
    }
}
