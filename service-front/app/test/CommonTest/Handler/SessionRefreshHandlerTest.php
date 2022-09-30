<?php

declare(strict_types=1);

namespace CommonTest\Handler;

use Common\Handler\SessionRefreshHandler;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

class SessionRefreshHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function testReturnsExpectedJsonResponseReturnsTrue()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $handler = new SessionRefreshHandler();

        $response = $handler->handle($requestProphecy->reveal());
        $json     = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_refreshed', $json);
        $this->assertTrue($json['session_refreshed']);
    }
}
