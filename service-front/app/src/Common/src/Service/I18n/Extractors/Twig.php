<?php

declare(strict_types=1);

namespace Common\Service\I18n\Extractors;

use Acpr\I18n\ExtractorInterface;
use Common\Service\I18n\CatalogueExtractor;
use Gettext\Merge;
use Gettext\Translations;

class Twig extends CatalogueExtractor
{
    public const MERGE_FLAGS =
        Merge::REFERENCES_THEIRS
        | Merge::EXTRACTED_COMMENTS_THEIRS;

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
        $this->catalogues = [];
    }

    public function extract(array $twigPaths): array
    {
        // Generate new POT catalogue/s
        foreach ($twigPaths as $path) {
            $translations = $this->parseFiles($path);
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
}
