<?php

declare(strict_types=1);

namespace ActorTest\Handler\Factory;

use PHPUnit\Framework\Attributes\Test;
use Actor\Handler\Factory\LogoutPageHandlerFactory;
use Common\Service\OneLogin\OneLoginService;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LogoutPageHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ContainerInterface|ObjectProphecy $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->container
            ->get(TemplateRendererInterface::class)
            ->willReturn($this->prophesize(TemplateRendererInterface::class)->reveal());

        $this->container
            ->get(UrlHelper::class)
            ->willReturn($this->prophesize(UrlHelper::class)->reveal());

        $this->container
            ->get(AuthenticationInterface::class)
            ->willReturn($this->prophesize(AuthenticationInterface::class)->reveal());

        $this->container
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
    }

    #[Test]
    public function it_creates_an_appropriate_logout_page_handler(): void
    {
        $this->container
            ->get(OneLoginService::class)
            ->shouldBeCalled()
            ->willReturn($this->prophesize(OneLoginService::class)->reveal());

        $factory = new LogoutPageHandlerFactory();

        ($factory)($this->container->reveal());
    }
}
