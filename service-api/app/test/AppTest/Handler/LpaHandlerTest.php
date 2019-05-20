<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpaHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class PingHandlerTest extends TestCase
{
    public function testResponse()
    {
        $lpaHandler = new LpaHandler();

        $requestProphercy = $this->prophesize(ServerRequestInterface::class);

        $requestProphercy->getAttribute('shareCode')
            ->willReturn(123456789012);

        /** @var JsonResponse $response */
        $response = $lpaHandler->handle($requestProphercy->reveal());

        $data = $response->getPayload();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayHasKey('shareCode', $data);
        $this->assertEquals(123456789012, $data['shareCode']);
    }
}
