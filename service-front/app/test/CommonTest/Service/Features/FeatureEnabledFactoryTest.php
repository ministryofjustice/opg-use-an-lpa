<?php

declare(strict_types=1);

namespace CommonTest\Service\Features;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

#[CoversClass(FeatureEnabledFactory::class)]
class FeatureEnabledFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_featureenabled_service_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'feature_flags' => [],
                ]
            );

        $factory = new FeatureEnabledFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(FeatureEnabled::class, $instance);
    }

    #[Test]
    public function it_will_throw_an_exception_if_not_configured(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new FeatureEnabledFactory();

        $this->expectException(UnexpectedValueException::class);
        $instance = $factory($containerProphecy->reveal());
    }

    #[Test]
    public function it_will_throw_an_exception_if_badly_configured(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'feature_flags' => 'not an array',
                ]
            );

        $factory = new FeatureEnabledFactory();

        $this->expectException(UnexpectedValueException::class);
        $instance = $factory($containerProphecy->reveal());
    }
}
