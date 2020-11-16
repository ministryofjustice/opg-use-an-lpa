<?php

declare(strict_types=1);

namespace Common\Service\I18n\Extractors;

use Acpr\I18n\ExtractorInterface;
use Acpr\I18n\PhpExtractor;
use Common\Service\I18n\CatalogueExtractor;

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
     * @return CatalogueExtractor
     */
    public function __invoke(): CatalogueExtractor
    {
        return new CatalogueExtractor(
            $this->extractor
        );
    }
}
