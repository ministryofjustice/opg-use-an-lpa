<?php

declare(strict_types=1);

namespace CommonTest\Middleware\I18n;

use Acpr\I18n\Translator;
use Common\Middleware\I18n\SetLocaleMiddleware;
use Common\Middleware\I18n\SetLocaleMiddlewareFactory;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SetLocaleMiddlewareFactoryTest extends TestCase
{
    /** @test */
    public function it_can_be_configured_with_a_default_locale(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('config')
            ->willReturn(true);
        $containerProphecy->get('config')
            ->shouldBeCalled()
            ->willReturn(['i18n' => ['default_locale' => 'en_GB']]);
        $containerProphecy->get(UrlHelper::class)
            ->willReturn($this->prophesize(UrlHelper::class)->reveal());
        $containerProphecy->get(Translator::class)
            ->willReturn($this->prophesize(Translator::class)->reveal());

        $factory = new SetLocaleMiddlewareFactory();
        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(SetLocaleMiddleware::class, $instance);
    }
}
