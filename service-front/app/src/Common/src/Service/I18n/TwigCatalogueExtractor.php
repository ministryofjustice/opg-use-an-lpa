<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Gettext\Merge;
use Gettext\Translations;

class TwigCatalogueExtractor
{
    public const MERGE_FLAGS =
        Merge::REFERENCES_THEIRS
        | Merge::EXTRACTED_COMMENTS_THEIRS;

    /** @var Translations[] $catalogues */
    private array $catalogues;

    /** @var Translations[] $existing */
    private array $existing;

    private ExtractorInterface $extractor;

    /**
     * TwigCatalogueExtractor constructor.
     *
     * @param ExtractorInterface $extractor
     * @param Translations[] $existing
     */
    public function __construct(
        ExtractorInterface $extractor,
        array $existing = []
    ) {
        $this->extractor = $extractor;

        $this->existing = $existing;
        $this->catalogues = [];
    }

    public function extract(array $twigPaths): array
    {
        // Generate new POT catalogue/s
        foreach ($twigPaths as $path) {
            $translations = $this->parseTemplates($path);
            array_walk($translations, [$this, 'mergeCatalogues']);
        }

        // Merge with existing
        foreach ($this->catalogues as $domain => $translations) {
            if (in_array($domain, array_keys($this->existing))) {
                $this->existing[$domain] =
                    $this->existing[$domain]->mergeWith($this->catalogues[$domain], self::MERGE_FLAGS);
            } else {
                $this->existing[$domain] = $translations;
            }
        }


        return $this->existing;
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
