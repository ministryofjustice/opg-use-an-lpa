<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\Token;

use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilder;
use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilderFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @coversDefaultClass \App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilderFactory
 */
class OutOfBandCoreIdentityVerifierBuilderFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers ::__invoke()
     */
    public function it_builds_a_verifier_builder(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'one_login' => [
                    'identity_issuer' => 'https://fakeUrl',
                ],
            ]
        );
        $container->get(ClockInterface::class)->willReturn($this->prophesize(ClockInterface::class)->reveal());

        $sut = new OutOfBandCoreIdentityVerifierBuilderFactory();

        $result = $sut($container->reveal());

        $this->assertInstanceOf(OutOfBandCoreIdentityVerifierBuilder::class, $result);
    }

    /**
     * @test
     * @covers ::__invoke()
     */
    public function it_throws_an_exception_when_config_missing(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([]);

        $sut = new OutOfBandCoreIdentityVerifierBuilderFactory();

        $this->expectException(RuntimeException::class);
        $result = $sut($container->reveal());
    }
}
