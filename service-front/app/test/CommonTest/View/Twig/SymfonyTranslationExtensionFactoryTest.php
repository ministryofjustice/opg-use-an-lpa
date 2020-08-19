<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\SymfonyTranslationExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class SymfonyTranslationExtensionFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_an_extension(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(TranslatorInterface::class)
            ->willReturn($this->prophesize(TranslatorInterface::class)->reveal());

        $factory = new SymfonyTranslationExtensionFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslationExtension::class, $instance);
    }
}
