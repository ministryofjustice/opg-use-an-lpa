<?php

declare(strict_types=1);

namespace CommonTest\Command;

use Common\Command\TranslationUpdateCommand;
use Common\Command\TranslationUpdateCommandFactory;
use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_translation_update_command(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(TwigCatalogueExtractor::class)
            ->willReturn($this->prophesize(TwigCatalogueExtractor::class)->reveal());
        $containerProphecy->get(PotGenerator::class)
            ->willReturn($this->prophesize(PotGenerator::class)->reveal());

        $factory = new TranslationUpdateCommandFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslationUpdateCommand::class, $instance);
    }
}
