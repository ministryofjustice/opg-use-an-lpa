<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Acpr\I18n\ExtractorInterface;
use Gettext\Merge;
use Gettext\Translations;

class CatalogueExtractor
{
    public const MERGE_FLAGS =
        Merge::REFERENCES_THEIRS
        | Merge::EXTRACTED_COMMENTS_THEIRS;

    protected ExtractorInterface $extractor;

    /** @var Translations[] $existing */
    private array $existing;

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
    }

    public function extract(array $twigPaths): array
    {
        $catalogues = [];

        // Generate new POT catalogue/s
        foreach ($twigPaths as $path) {
            $found = $this->parseFiles($path);

            foreach ($found as $domain => $translations) {
                $this->mergeIntoCatalogue($catalogues, $translations, $domain);
            }
        }

        $this->mergeCatalogues($this->existing, $catalogues);

        return $this->existing;
    }

    /**
     * Merges two catalogues of translations together specifying that the existing catalogue be the starting
     * point for any changes.
     *
     * @param Translations[] $existing
     * @param Translations[] $catalogues
     */
    protected function mergeCatalogues(array &$existing, array $catalogues): void
    {
        // Merge with existing
        foreach ($catalogues as $domain => $translations) {
            $this->mergeIntoCatalogue($existing, $translations, $domain, self::MERGE_FLAGS);
        }
    }

    /**
     * Merges a set of translations with a specified domain into and existing set of catalogues.
     *
     * @param Translations[] $catalogues
     * @param Translations $translations
     * @param string $domain
     * @param int $mergeFlags
     */
    protected function mergeIntoCatalogue(
        array &$catalogues,
        Translations $translations,
        string $domain,
        int $mergeFlags = 0
    ): void {
        if (in_array($domain, array_keys($catalogues))) {
            $catalogues[$domain] = $catalogues[$domain]->mergeWith($translations, $mergeFlags);
        } else {
            $catalogues[$domain] = $translations;
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
