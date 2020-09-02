<?php

declare(strict_types=1);

namespace CommonTest\Command;

use Common\Command\TranslationUpdateCommand;
use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractor;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Tester\CommandTester;

class TranslationUpdateCommandTest extends TestCase
{
    /** @test */
    public function it_can_be_executed(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>'
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var TwigCatalogueExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(TwigCatalogueExtractor::class);
        $extractorProphecy
            ->extract([$vfs->url()])
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );

        $generatorProphecy = $this->prophesize(PotGenerator::class);
        $generatorProphecy
            ->generate(['messages' => $translationsProphecy->reveal()])
            ->willReturn(
                1
            );

        $command = new TranslationUpdateCommand(
            $extractorProphecy->reveal(),
            $generatorProphecy->reveal(),
            [$vfs->url()]
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Translation files were successfully updated', $output);
    }
}
