<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Gettext\Translations;

abstract class CatalogueExtractor
{
    protected ExtractorInterface $extractor;

    /** @var Translations[] $catalogues */
    protected array $catalogues;

    protected function mergeCatalogues(Translations $translations, string $domain): void
    {
        if (in_array($domain, array_keys($this->catalogues))) {
            $this->catalogues[$domain] = $this->catalogues[$domain]->mergeWith($translations);
        } else {
            $this->catalogues[$domain] = $translations;
        }
    }

    /**
     * @param string $path
     * @return Translations[]
     */
    protected function parseFiles(string $path): array
    {
        $translations = [];

        if (is_dir($path) || is_file($path)) {
            $translations = $this->extractor->extract($path);
        }

        return $translations;
    }
}
