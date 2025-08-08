<?php

declare(strict_types=1);

namespace CommonTest\Handler\Factory;

use Acpr\I18n\TranslatorInterface;
use Common\Handler\Factory\CookiesPageHandlerFactory;
use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CookiesPageHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
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
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
        $containerProphecy
            ->get(UrlValidityCheckService::class)
            ->willReturn($this->prophesize(UrlValidityCheckService::class)->reveal());
        $containerProphecy
            ->get(TranslatorInterface::class)
            ->willReturn($this->prophesize(TranslatorInterface::class)->reveal());

        $factory = new CookiesPageHandlerFactory();

        $this->expectNotToPerformAssertions();
        $factory($containerProphecy->reveal());
    }

    #[Test]
    public function it_needs_an_application_configuration_value(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new CookiesPageHandlerFactory();

        $this->expectException(RuntimeException::class);
        $factory($containerProphecy->reveal());
    }
}
