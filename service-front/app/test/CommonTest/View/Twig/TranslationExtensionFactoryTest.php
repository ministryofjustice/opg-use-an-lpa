<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Acpr\I18n\NodeVisitor\TranslationNodeVisitor;
use Acpr\I18n\TranslationExtension;
use Acpr\I18n\TranslatorInterface;
use Common\View\Twig\TranslationExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TranslationExtensionFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_an_extension(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(TranslatorInterface::class)
            ->willReturn($this->prophesize(TranslatorInterface::class)->reveal());
        $containerProphecy->get(TranslationNodeVisitor::class)
            ->willReturn(new TranslationNodeVisitor()); // Visitor is marked final

        $factory = new TranslationExtensionFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslationExtension::class, $instance);
    }
}
