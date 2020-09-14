<?php

declare(strict_types=1);

namespace CommonTest\Handler;

use Common\Handler\SessionCheckHandler;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Exception\RuntimeException;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionCheckHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function testReturnsExpectedJsonResponseReturnsFalse()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->willReturn(time());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $handler = new SessionCheckHandler(1200);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_warning', $json);
        $this->assertFalse($json['session_warning']);

        $this->assertArrayHasKey('time_remaining', $json);
        $this->assertIsInt($json['time_remaining']);
    }

    /**
     * @test
     */
    public function testReturnsExpectedJsonResponseReturnsTrue()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->willReturn((time() - 950));

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());

        $handler = new SessionCheckHandler(1200);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_warning', $json);
        $this->assertTrue($json['session_warning']);

        $this->assertArrayHasKey('time_remaining', $json);
        $this->assertIsInt($json['time_remaining']);
    }
}
