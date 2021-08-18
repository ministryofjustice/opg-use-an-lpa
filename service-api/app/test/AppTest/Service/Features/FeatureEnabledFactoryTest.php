<?php

declare(strict_types=1);

namespace AppTest\Service\Features;

use App\Service\Features\FeatureEnabled;
use App\Service\Features\FeatureEnabledFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

/**
 * Class FeatureEnabledFactoryTest
 *
 * @package AppTest\Service\Features
 *
 * @coversDefaultClass \App\Service\Features\FeatureEnabledFactory
 */
class FeatureEnabledFactoryTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
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

    /**
     * @test
     * @covers ::__invoke
     */
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

    /**
     * @test
     * @covers ::__invoke
     */
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
