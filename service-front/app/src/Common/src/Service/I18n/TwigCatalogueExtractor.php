<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Gettext\Translations;

class TwigCatalogueExtractor
{
    /** @var Translations[] $catalogues */
    private array $catalogues;

    private ExtractorInterface $extractor;

    public function __construct(
        ExtractorInterface $extractor
    ) {
        $this->extractor = $extractor;

        $this->catalogues = [];
    }

    public function extract(array $twigPaths): array
    {
        foreach ($twigPaths as $path) {
            $translations = $this->parseTemplates($path);
            array_walk($translations, [$this, 'mergeCatalogues']);
        }

        return $this->catalogues;
    }

    /**
     * @param string $path
     * @return Translations[]
     */
    protected function parseTemplates(string $path): array
    {
        $translations = [];

        if (is_dir($path) || is_file($path)) {
            $translations = $this->extractor->extract($path);
        }

        return $translations;
    }

    /**
     * @param Translations $translations
     * @param string $domain
     */
    protected function mergeCatalogues(Translations $translations, string $domain): void
    {
        if (in_array($domain, array_keys($this->catalogues))) {
            $this->catalogues[$domain] = $this->catalogues[$domain]->mergeWith($translations);
        } else {
            $this->catalogues[$domain] = $translations;
        }
    }
}
