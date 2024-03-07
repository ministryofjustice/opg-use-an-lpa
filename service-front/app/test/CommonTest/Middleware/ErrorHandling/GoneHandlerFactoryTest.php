<?php

declare(strict_types=1);

namespace CommonTest\Middleware\ErrorHandling;

use Common\Middleware\ErrorHandling\GoneHandler;
use Common\Middleware\ErrorHandling\GoneHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class GoneHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFactoryReturnsGoneHandlerInstance(): void
    {
        $container        = $this->prophesize(ContainerInterface::class);
        $templateRenderer = $this->prophesize(TemplateRendererInterface::class);
        $responseFactory  = $this->prophesize(ResponseFactoryInterface::class);

        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory->createResponse(410)->willReturn($responseMock);

        $container->get(TemplateRendererInterface::class)->willReturn($templateRenderer->reveal());
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal());
        $container->has(ResponseFactoryInterface::class)->willReturn(true);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([]);

        $factory = new GoneHandlerFactory();

        $goneHandler = $factory($container->reveal());

        $this->assertInstanceOf(GoneHandler::class, $goneHandler);
    }
}
