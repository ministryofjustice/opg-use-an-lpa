<?php

declare(strict_types = 1);

namespace CommonTest\View\Twig;

use Common\View\Twig\GenericGlobalVariableExtension;
use Common\View\Twig\GenericGlobalVariableExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class GenericGlobalVariableExtensionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_an_instance_of_the_variable_extension()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn(
                [
                    'application' => 'actor',
                ]
            );
        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $factory = new GenericGlobalVariableExtensionFactory();
        $genericConfig = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(GenericGlobalVariableExtension::class, $genericConfig);
    }

    /**
     * @test
     */
    public function throws_exception_when_missing_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn([]);

        $factory = new GenericGlobalVariableExtensionFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing application type, should be one of "viewer" or "actor"');
        $genericConfig = $factory($containerProphecy->reveal());
    }
}
