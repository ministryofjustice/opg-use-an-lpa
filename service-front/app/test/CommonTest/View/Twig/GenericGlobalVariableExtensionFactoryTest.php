<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use PHPUnit\Framework\Attributes\Test;
use Acpr\I18n\TranslatorInterface;
use Common\View\Twig\GenericGlobalVariableExtension;
use Common\View\Twig\GenericGlobalVariableExtensionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class GenericGlobalVariableExtensionFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_create_an_instance_of_the_variable_extension(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn(
                [
                    'application' => 'actor',
                ]
            );

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $translationProphecy = $this->prophesize(TranslatorInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $containerProphecy->get(TranslatorInterface::class)
            ->willReturn($translationProphecy->reveal());

        $factory = new GenericGlobalVariableExtensionFactory();

        $genericConfig = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(GenericGlobalVariableExtension::class, $genericConfig);
    }

    #[Test]
    public function throws_exception_when_missing_configuration(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $translationProphecy = $this->prophesize(TranslatorInterface::class);

        $containerProphecy->get('config')->willReturn([]);

        $containerProphecy->get(TranslatorInterface::class)
            ->willReturn($translationProphecy->reveal());

        $factory = new GenericGlobalVariableExtensionFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing application type, should be one of "viewer" or "actor"');
        $genericConfig = $factory($containerProphecy->reveal());
    }
}
