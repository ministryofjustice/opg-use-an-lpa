<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Viewer\Handler\HomePageHandler;
use Viewer\Handler\HomePageHandlerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class HomePageHandlerFactoryTest extends TestCase
{
    public function testInvoke()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $renderer = $this->prophesize(TemplateRendererInterface::class);
        $container->get(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $urlHelper = $this->prophesize(UrlHelper::class);
        $container->get(UrlHelper::class)
            ->willReturn($urlHelper);

        $factory = new HomePageHandlerFactory();

        $this->assertInstanceOf(HomePageHandlerFactory::class, $factory);

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HomePageHandler::class, $handler);
    }
}
