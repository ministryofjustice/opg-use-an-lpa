<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Common\Service\I18n\TwigCatalogueExtractor;
use Common\Service\I18n\TwigCatalogueExtractorFactory;
use PHPUnit\Framework\TestCase;

class TwigCatalogueExtractorFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_an_extractor(): void
    {
        $factory = new TwigCatalogueExtractorFactory(
            $this->prophesize(ExtractorInterface::class)->reveal()
        );

        $extractor = $factory([]);

        $this->assertInstanceOf(TwigCatalogueExtractor::class, $extractor);
    }
}
