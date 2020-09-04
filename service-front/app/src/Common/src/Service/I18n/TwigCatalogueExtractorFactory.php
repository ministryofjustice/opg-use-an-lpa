<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Gettext\Translations;

class TwigCatalogueExtractorFactory
{
    private ExtractorInterface $extractor;

    public function __construct(ExtractorInterface $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * Allows the creation of an extractor that is aware of previous extractions.
     *
     * @param Translations[] $existing
     * @return TwigCatalogueExtractor
     */
    public function __invoke(array $existing): TwigCatalogueExtractor
    {
        return new TwigCatalogueExtractor(
            $this->extractor,
            $existing
        );
    }
}
