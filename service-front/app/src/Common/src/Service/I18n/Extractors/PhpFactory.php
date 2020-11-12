<?php

declare(strict_types=1);

namespace Common\Service\I18n\Extractors;

use Acpr\I18n\ExtractorInterface;
use Acpr\I18n\PhpExtractor;
use Common\Service\I18n\CatalogueExtractor;
use Gettext\Translations;

class PhpFactory
{
    private ExtractorInterface $extractor;

    public function __construct(PhpExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * Allows the creation of an extractor that is aware of previous extractions.
     *
     * @param Translations[] $existing
     * @return CatalogueExtractor
     */
    public function __invoke(array $existing): CatalogueExtractor
    {
        return new CatalogueExtractor(
            $this->extractor,
            $existing
        );
    }
}
