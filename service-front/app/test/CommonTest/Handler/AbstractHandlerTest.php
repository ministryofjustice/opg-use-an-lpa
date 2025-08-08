<?php

declare(strict_types=1);

namespace CommonTest\Handler;

use Common\Handler\AbstractHandler;
use Exception;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class AbstractHandlerTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function redirectToRouteWithOverride(): void
    {
        $localOverride = 'cy';
        /** @var UrlHelper|ObjectProphecy $urlHelperMock */
        $urlHelperMock = $this->prophesize(UrlHelper::class);
        $urlHelperMock->setBasePath($localOverride)->shouldBeCalledOnce();
        $urlHelperMock->generate(
            'fake-route',
            ['fake-route-parameters' => ''],
            ['fake-query-parameters' => ''],
        )->willReturn('/cy/fake-route');

        $renderer = $this->prophesize(TemplateRendererInterface::class);

        $logger = $this->prophesize(LoggerInterface::class);

        $abstractHandler = new class ($renderer->reveal(), $urlHelperMock->reveal(), $logger->reveal()) extends AbstractHandler {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Not implemented');
            }
        };
        $response        = $abstractHandler->redirectToRoute(
            'fake-route',
            ['fake-route-parameters' => ''],
            ['fake-query-parameters' => ''],
            $localOverride,
        );
        $this->assertEquals('/cy/fake-route', $response->getHeader('location')[0]);
    }

    #[Test]
    public function testRedirectToRouteOverrideSetToNull(): void
    {
        /** @var UrlHelper|ObjectProphecy $urlHelperMock */
        $urlHelperMock = $this->prophesize(UrlHelper::class);
        $urlHelperMock->setBasePath(Argument::any())->shouldNotBeCalled();
        $urlHelperMock->generate('fake-route', [], [])->willReturn('/fake-route');

        $renderer = $this->prophesize(TemplateRendererInterface::class);

        $logger = $this->prophesize(LoggerInterface::class);

        $abstractHandler = new class ($renderer->reveal(), $urlHelperMock->reveal(), $logger->reveal()) extends AbstractHandler {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Not implemented');
            }
        };
        $response        = $abstractHandler->redirectToRoute('fake-route');
        $this->assertEquals('/fake-route', $response->getHeader('location')[0]);
    }
}
