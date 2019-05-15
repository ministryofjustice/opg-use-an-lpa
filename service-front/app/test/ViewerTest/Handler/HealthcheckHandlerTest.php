<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Handler\HealthcheckHandler;
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
    }
}
