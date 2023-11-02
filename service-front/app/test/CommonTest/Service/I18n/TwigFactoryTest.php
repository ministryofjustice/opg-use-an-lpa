<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Acpr\I18n\TwigExtractor;
use Common\Service\I18n\CatalogueExtractor;
use Common\Service\I18n\Extractors\TwigFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TwigFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_an_extractor(): void
    {
        $factory = new TwigFactory(
            $this->prophesize(TwigExtractor::class)->reveal()
        );

        $extractor = $factory();

        $this->assertInstanceOf(CatalogueExtractor::class, $extractor);
    }
}
