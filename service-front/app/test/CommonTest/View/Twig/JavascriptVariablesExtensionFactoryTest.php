<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\JavascriptVariablesExtension;
use Common\View\Twig\JavascriptVariablesExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class JavascriptVariablesExtensionFactoryTest extends TestCase
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
                    'analytics' => [
                            'uaid' => 'uaid1234',
                    ],
                ]
            );

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $factory = new JavascriptVariablesExtensionFactory();

        $analyticsConfig = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(JavascriptVariablesExtension::class, $analyticsConfig);
    }

    /**
     * @test
     */
    public function throws_exception_when_missing_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn([]);

        $factory = new JavascriptVariablesExtensionFactory();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing google analytics ua id');
        $analyticsConfig = $factory($containerProphecy->reveal());
    }
}
