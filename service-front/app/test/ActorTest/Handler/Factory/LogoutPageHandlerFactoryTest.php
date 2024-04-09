<?php

declare(strict_types=1);

namespace ActorTest\Handler\Factory;

use Actor\Handler\Factory\LogoutPageHandlerFactory;
use Common\Service\Authentication\LocalAccountLogout;
use Common\Service\Authentication\LogoutStrategy;
use Common\Service\Features\FeatureEnabled;
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

    public function featureFlagStrategies(): array
    {
        return [
            'one-login disabled' => [
                false,
                LocalAccountLogout::class,
            ],
            'one-login enabled'  => [
                true,
                OneLoginService::class,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider featureFlagStrategies
     *
     * @psalm-param class-string<LogoutStrategy> $strategyClass
     */
    public function it_creates_an_appropriate_logout_page_handler(
        bool $allowGovOneLogin,
        string $strategyClass
    ): void {
        $featureProphecy = $this->prophesize(FeatureEnabled::class);
        $featureProphecy
            ->__invoke('allow_gov_one_login')
            ->willReturn($allowGovOneLogin);

        $this->container
            ->get($strategyClass)
            ->shouldBeCalled()
            ->willReturn($this->prophesize($strategyClass)->reveal());

        $this->container
            ->get(FeatureEnabled::class)
            ->willReturn(
                $featureProphecy->reveal(),
            );

        $factory = new LogoutPageHandlerFactory();

        ($factory)($this->container->reveal());
    }
}
