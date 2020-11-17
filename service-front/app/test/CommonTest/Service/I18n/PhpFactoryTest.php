<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Acpr\I18n\PhpExtractor;
use Common\Service\I18n\CatalogueExtractor;
use Common\Service\I18n\Extractors\PhpFactory;
use PHPUnit\Framework\TestCase;

class PhpFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_an_extractor(): void
    {
        $factory = new PhpFactory(
            $this->prophesize(PhpExtractor::class)->reveal()
        );

        $extractor = $factory();

        $this->assertInstanceOf(CatalogueExtractor::class, $extractor);
    }
}
