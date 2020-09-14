<?php

declare(strict_types=1);

namespace CommonTest\Handler\Factory;

use Common\Handler\CookiesPageHandler;
use Common\Handler\Factory\CookiesPageHandlerFactory;
use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class CookiesPageHandlerFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_a_CookiesPageHandler(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(['application' => 'viewer']);
        $containerProphecy
            ->get(TemplateRendererInterface::class)
            ->willReturn($this->prophesize(TemplateRendererInterface::class)->reveal());
        $containerProphecy
            ->get(UrlHelper::class)
            ->willReturn($this->prophesize(UrlHelper::class)->reveal());
        $containerProphecy
            ->get(UrlValidityCheckService::class)
            ->willReturn($this->prophesize(UrlValidityCheckService::class)->reveal());

        $factory = new CookiesPageHandlerFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(CookiesPageHandler::class, $instance);
    }

    /** @test */
    public function it_needs_an_application_configuration_value(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new CookiesPageHandlerFactory();

        $this->expectException(RuntimeException::class);
        $instance = $factory($containerProphecy->reveal());
    }
}
