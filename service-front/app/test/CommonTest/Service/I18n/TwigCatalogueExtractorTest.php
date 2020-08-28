<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Acpr\I18n\TwigExtractor;
use Common\Service\I18n\TwigCatalogueExtractor;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class TwigCatalogueExtractorTest extends TestCase
{
    /** @test */
    public function it_will_extract_a_single_file(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>'
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract($vfs->getChild('home.html.twig')->url())
            ->shouldBeCalled()
            ->willReturn(
                [ 'messages' => $translationsProphecy->reveal() ]
            );

        $extractor = new TwigCatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->getChild('home.html.twig')->url()]);

        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }

    /** @test */
    public function it_will_extract_a_folder(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>'
            ]
        );

        $translationsProphecy = $this->prophesize(Translations::class);

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract($vfs->url())
            ->shouldBeCalled()
            ->willReturn(
                [ 'messages' => $translationsProphecy->reveal() ]
            );

        $extractor = new TwigCatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->url()]);

        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }

    /** @test */
    public function it_will_merge_catalogues_across_extractions(): void
    {
        $vfs = vfsStream::setup(
            'rootDir',
            null,
            [
                'home.html.twig' => '<h1>{%trans%}Some translated twig content{%endtrans%}</h1>'
            ]
        );

        /** @var Translations|ObjectProphecy $translationsProphecy */
        $translationsProphecy = $this->prophesize(Translations::class);
        $translationsProphecy
            ->mergeWith(Argument::type(Translations::class))
            ->shouldBeCalled()
            ->willReturn(
                $translationsProphecy->reveal()
            );

        /** @var TwigExtractor|ObjectProphecy $extractorProphecy */
        $extractorProphecy = $this->prophesize(ExtractorInterface::class);
        $extractorProphecy
            ->extract($vfs->url())
            ->shouldBeCalled()
            ->willReturn(
                [ 'messages' => $translationsProphecy->reveal() ]
            );

        $extractor = new TwigCatalogueExtractor($extractorProphecy->reveal());

        $translations = $extractor->extract([$vfs->url()]);
        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);

        $translations = $extractor->extract([$vfs->url()]);
        $this->assertEquals(['messages' => $translationsProphecy->reveal()], $translations);
    }
}
