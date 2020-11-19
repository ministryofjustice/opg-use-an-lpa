<?php

declare(strict_types=1);

namespace CommonTest\Command;

use Common\Command\TranslationUpdateCommand;
use Common\Service\I18n\CatalogueExtractor;
use Common\Service\I18n\CatalogueLoader;
use Common\Service\I18n\Extractors\PhpFactory;
use Common\Service\I18n\Extractors\TwigFactory;
use Common\Service\I18n\PotGenerator;
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
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>',
                'home.php' => '<?php $translator->translate("Some translated PHP content", []);'
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var CatalogueExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(CatalogueExtractor::class);
        $extractorProphecy
            ->extract([$vfs->url()])
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );
        $extractorProphecy
            ->mergeCatalogues(
                ['messages' => $translationsProphecy->reveal()],
                ['messages' => $translationsProphecy->reveal()],
                0
            )
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );
        $extractorProphecy
            ->mergeCatalogues(
                ['messages' => $translationsProphecy->reveal()],
                ['messages' => $translationsProphecy->reveal()]
            )
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );


        /** @var TwigFactory|ObjectProphecy $extractorProphecy */
        $twigExtractorFactoryProphecy = $this->prophesize(TwigFactory::class);
        $twigExtractorFactoryProphecy
            ->__invoke()
            ->willReturn(
                $extractorProphecy->reveal()
            );

        /** @var PhpFactory|ObjectProphecy $extractorProphecy */
        $phpExtractorFactoryProphecy = $this->prophesize(PhpFactory::class);
        $phpExtractorFactoryProphecy
            ->__invoke()
            ->willReturn(
                $extractorProphecy->reveal()
            );

        $loaderProphecy = $this->prophesize(CatalogueLoader::class);
        $loaderProphecy->loadByDirectory('languages/')
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
            $twigExtractorFactoryProphecy->reveal(),
            $phpExtractorFactoryProphecy->reveal(),
            $loaderProphecy->reveal(),
            $generatorProphecy->reveal(),
            [$vfs->url()],
            [$vfs->url()]
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Translation files were successfully updated', $output);
    }
}
