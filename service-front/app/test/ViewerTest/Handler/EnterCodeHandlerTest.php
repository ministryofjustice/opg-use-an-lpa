<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Service\SystemMessage\SystemMessageService;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Exception;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Viewer\Handler\EnterCodeHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

class EnterCodeHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testGetRequest()
    {
        $rendererProphecy      = $this->prophesize(TemplateRendererInterface::class);
        $urlHelperProphecy     = $this->prophesize(UrlHelper::class);
        $systemMessageProphecy = $this->prophesize(SystemMessageService::class);
        $csrfGuardProphecy     = $this->prophesize(CsrfGuardInterface::class);
        $sessionProphecy       = $this->prophesize(SessionInterface::class);

        $rendererProphecy->render('viewer::enter-code', Argument::any())
            ->willReturn('');

        $systemMessageProphecy->getMessages()->willReturn([]);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $systemMessageProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()->willReturn('GET');
        $requestProphecy->getAttribute('session')->willReturn($sessionProphecy->reveal());
        $requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)->willReturn($csrfGuardProphecy->reveal());

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
