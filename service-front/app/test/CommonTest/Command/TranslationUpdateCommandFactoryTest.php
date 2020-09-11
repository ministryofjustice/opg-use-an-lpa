<?php

declare(strict_types=1);

namespace CommonTest\Command;

use Common\Command\TranslationUpdateCommand;
use Common\Command\TranslationUpdateCommandFactory;
use Common\Service\I18n\CatalogueLoader;
use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_translation_update_command(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(TwigCatalogueExtractorFactory::class)
            ->willReturn($this->prophesize(TwigCatalogueExtractorFactory::class)->reveal());
        $containerProphecy->get(CatalogueLoader::class)
            ->willReturn($this->prophesize(CatalogueLoader::class)->reveal());
        $containerProphecy->get(PotGenerator::class)
            ->willReturn($this->prophesize(PotGenerator::class)->reveal());

        $factory = new TranslationUpdateCommandFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslationUpdateCommand::class, $instance);
    }
}
