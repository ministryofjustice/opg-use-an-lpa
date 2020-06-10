<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use Common\Service\Security\RateLimitServiceFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Viewer\Handler\CheckCodeHandler;
use Viewer\Handler\Factory\CheckCodeHandlerFactory;

class CheckCodeHandlerFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_correctly_configured_instance()
    {
        $rlsfProphecy = $this->prophesize(RateLimitServiceFactory::class);
        $rlsfProphecy
            ->factory('viewer_code_failure')
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
            ->get(LpaService::class)
            ->willReturn($this->prophesize(LpaService::class)->reveal());
        $containerProphecy
            ->get(RateLimitServiceFactory::class)
            ->willReturn($rlsfProphecy->reveal());

        $factory = new CheckCodeHandlerFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(CheckCodeHandler::class, $instance);
    }
}
