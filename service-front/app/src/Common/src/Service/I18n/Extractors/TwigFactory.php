<?php

declare(strict_types=1);

namespace Common\Service\I18n\Extractors;

use Acpr\I18n\ExtractorInterface;
use Gettext\Translations;

class TwigFactory
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
     * @return Twig
     */
    public function __invoke(array $existing): Twig
    {
        return new Twig(
            $this->extractor,
            $existing
        );
    }
}
