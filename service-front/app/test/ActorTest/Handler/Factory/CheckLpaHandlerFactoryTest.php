<?php

declare(strict_types=1);

namespace ActorTest\Handler\Factory;

use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use Common\Service\Security\RateLimitServiceFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Authentication\AuthenticationInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Actor\Handler\CheckLpaHandler;
use Actor\Handler\Factory\CheckLpaHandlerFactory;
use Psr\Log\LoggerInterface;
use Acpr\I18n\TranslatorInterface;

class CheckLPAHandlerFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_correctly_configured_instance()
    {
        $rlsfProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $rlsfProphecy
            ->factory('actor_code_failure')
            ->shouldBeCalled()
            ->willReturn($this->prophesize(RateLimitService::class)->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get(TemplateRendererInterface::class)
            ->willReturn($this->prophesize(TemplateRendererInterface::class)->reveal());
        $containerProphecy
            ->get(UrlHelper::class)
            ->willReturn($this->prophesize(UrlHelper::class)->reveal());
        $containerProphecy
            ->get(AuthenticationInterface::class)
            ->willReturn($this->prophesize(AuthenticationInterface::class)->reveal());
        $containerProphecy
            ->get(LpaService::class)
            ->willReturn($this->prophesize(LpaService::class)->reveal());
        $containerProphecy
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
        $containerProphecy
            ->get(TranslatorInterface::class)
            ->willReturn($this->prophesize(TranslatorInterface::class)->reveal());
        $containerProphecy
            ->get(RateLimitServiceFactory::class)
            ->willReturn($rlsfProphecy->reveal());

        $factory = new CheckLpaHandlerFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(CheckLpaHandler::class, $instance);
    }
}
