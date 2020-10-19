<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Handler\ActorSessionCheckHandler;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandlerTest extends TestCase
{
    /**
     * @var TemplateRendererInterface
     */
    private $templateRendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var AuthenticationInterface
     */
    private $authenticatorProphecy;

    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var UserInterface
     */
    private $userProphecy;

    public function setup()
    {
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->userProphecy = $this->prophesize(UserInterface::class);

        $this->authenticatorProphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->userProphecy->reveal());
    }

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

        $handler = new ActorSessionCheckHandler(
            $this->templateRendererProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            1200
        );

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

        $handler = new ActorSessionCheckHandler(
            $this->templateRendererProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            1200
        );

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_warning', $json);
        $this->assertTrue($json['session_warning']);

        $this->assertArrayHasKey('time_remaining', $json);
        $this->assertIsInt($json['time_remaining']);
    }
}
