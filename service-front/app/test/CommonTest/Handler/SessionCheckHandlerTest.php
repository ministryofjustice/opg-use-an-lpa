<?php

declare(strict_types=1);

namespace CommonTest\Handler;

use Common\Handler\SessionCheckHandler;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class SessionCheckHandlerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|TemplateRendererInterface $templateRendererProphecy;
    private ObjectProphecy|UrlHelper $urlHelperProphecy;
    private ObjectProphecy|LoggerInterface $loggerProphecy;
    private ObjectProphecy|UserInterface $userProphecy;

    public function setUp(): void
    {
        $this->templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $this->loggerProphecy           = $this->prophesize(LoggerInterface::class);
        $this->urlHelperProphecy        = $this->prophesize(UrlHelper::class);
        $this->userProphecy             = $this->prophesize(UserInterface::class);
    }

    #[Test]
    public function returnsExpectedJsonResponseReturnsFalse(): void
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->willReturn(time());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());
        $requestProphecy
            ->getAttribute(UserInterface::class)
            ->shouldBeCalled()
            ->willReturn($this->userProphecy->reveal());

        $handler = new SessionCheckHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            1200,
            300
        );

        $response = $handler->handle($requestProphecy->reveal());
        $json     = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_warning', $json);
        $this->assertFalse($json['session_warning']);

        $this->assertArrayHasKey('time_remaining', $json);
        $this->assertIsInt($json['time_remaining']);
    }

    #[Test]
    public function returnsExpectedJsonResponseReturnsTrue(): void
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
            ->willReturn(time() - 950);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());
        $requestProphecy
            ->getAttribute(UserInterface::class)
            ->shouldBeCalled()
            ->willReturn($this->userProphecy->reveal());

        $handler = new SessionCheckHandler(
            $this->templateRendererProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            1200,
            300
        );

        $response = $handler->handle($requestProphecy->reveal());
        $json     = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('session_warning', $json);
        $this->assertTrue($json['session_warning']);

        $this->assertArrayHasKey('time_remaining', $json);
        $this->assertIsInt($json['time_remaining']);
    }
}
