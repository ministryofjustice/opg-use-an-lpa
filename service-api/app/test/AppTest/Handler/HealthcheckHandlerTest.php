<?php

declare(strict_types=1);

namespace AppTest\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Handler\HealthcheckHandler;
use Zend\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    public function testReturnsJsonResponse()
    {
        //  Set up the handler
        $handler = new HealthcheckHandler();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode((string) $response->getBody());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertObjectHasAttribute('healthy', $json);
        $this->assertObjectHasAttribute('version', $json);
        $this->assertObjectHasAttribute('dependencies', $json);

        $dependencies = $json->dependencies;
        $this->assertObjectHasAttribute('api', $dependencies);

        $api = $dependencies->api;
        $this->assertObjectHasAttribute('healthy', $api);
        $this->assertObjectHasAttribute('version', $api);
    }
}
