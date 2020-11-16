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

    /**
     * TwigCatalogueExtractor constructor.
     *
     * @param ExtractorInterface $extractor
     */
    public function __construct(
        ExtractorInterface $extractor
    ) {
        $this->extractor = $extractor;
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

        return $catalogues;
    }

    /**
     * Merges two catalogues of translations together specifying that the existing catalogue be the starting
     * point for any changes.
     *
     * @param Translations[] $into An array of translations catalogues to merge new translations into
     * @param Translations[] $catalogue An array of translations to merge
     * @param int $strategy The merge strategy to use when merging catalogues
     * @return array The merged translation catalogues
     */
    public function mergeCatalogues(array $into, array $catalogue, int $strategy = self::MERGE_FLAGS): array
    {
        // Merge with existing
        foreach ($catalogue as $domain => $translations) {
            $this->mergeIntoCatalogue($into, $translations, $domain, $strategy);
        }

        return $into;
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
