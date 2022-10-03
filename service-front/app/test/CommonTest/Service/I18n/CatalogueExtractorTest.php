<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Acpr\I18n\TwigExtractor;
use Common\Service\I18n\CatalogueExtractor;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CatalogueExtractorTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_will_extract_a_single_twig_file(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>',
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract($vfs->getChild('home.html.twig')->url())
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );

        $extractor = new CatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->getChild('home.html.twig')->url()]);

        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }

    /** @test */
    public function it_will_extract_a_folder_of_twig(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>',
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract($vfs->url())
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );

        $extractor = new CatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->url()]);

        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }

    /** @test */
    public function it_will_merge_catalogues_across_extractions_of_twig(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>',
                'partials'       => [
                    'page.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>',
                ],
            ]
        );

        /** @var Translations|ObjectProphecy $translationsProphecy */
        $translationsProphecy = $this->prophesize(Translations::class);
        $translationsProphecy
            ->mergeWith(Argument::type(Translations::class), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                $translationsProphecy->reveal()
            );

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn(
                ['messages' => $translationsProphecy->reveal()]
            );

        $extractor = new CatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->url(), $vfs->getChild('partials')->url()]);
        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }

    /** @test */
    public function it_will_merge_translation_catalogues(): void
    {
        /** @var Translations|ObjectProphecy $originalTranslationsProphecy */
        $originalTranslationsProphecy = $this->prophesize(Translations::class);
        $originalTranslationsProphecy
            ->mergeWith(Argument::type(Translations::class), Argument::that(function ($arg) {
                $this->assertEquals(8704, $arg, 'The merge strategy is incorrect');
                return true;
            }))
            ->shouldBeCalled()
            ->willReturn(
                $originalTranslationsProphecy->reveal()
            );
        $originalTranslations = [
            'default' => $originalTranslationsProphecy->reveal(),
            'errors'  => $originalTranslationsProphecy->reveal(),
        ];

        /** @var Translations|ObjectProphecy $newTranslationsProphecy */
        $newTranslationsProphecy = $this->prophesize(Translations::class);
        $newTranslations         = [
            'default' => $newTranslationsProphecy->reveal(),
            'new'     => $newTranslationsProphecy->reveal(),
        ];

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);

        $extractor = new CatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->mergeCatalogues($originalTranslations, $newTranslations);

        $this->assertCount(3, $translations);
    }
}
