<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;

class TwigCatalogueExtractorFactory
{
    private ExtractorInterface $extractor;

    public function __construct(ExtractorInterface $extractor)
    {
        $this->extractor = $extractor;
    }

    public function __invoke($existing): TwigCatalogueExtractor
    {
        return new TwigCatalogueExtractor(
            $this->extractor,
            $existing
        );
    }
}
