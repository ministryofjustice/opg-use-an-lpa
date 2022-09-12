<?php

declare(strict_types=1);

namespace CommonTest\Command;

use Common\Command\TranslationUpdateCommand;
use Common\Command\TranslationUpdateCommandFactory;
use Common\Service\I18n\CatalogueLoader;
use Common\Service\I18n\Extractors\PhpFactory;
use Common\Service\I18n\Extractors\TwigFactory;
use Common\Service\I18n\PotGenerator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_a_translation_update_command(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(TwigFactory::class)
            ->willReturn($this->prophesize(TwigFactory::class)->reveal());
        $containerProphecy->get(PhpFactory::class)
            ->willReturn($this->prophesize(PhpFactory::class)->reveal());
        $containerProphecy->get(CatalogueLoader::class)
            ->willReturn($this->prophesize(CatalogueLoader::class)->reveal());
        $containerProphecy->get(PotGenerator::class)
            ->willReturn($this->prophesize(PotGenerator::class)->reveal());

        $factory = new TranslationUpdateCommandFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslationUpdateCommand::class, $instance);
    }
}
