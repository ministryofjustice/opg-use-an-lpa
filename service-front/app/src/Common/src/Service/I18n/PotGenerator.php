<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use DateTime;
use Gettext\Generator\GeneratorInterface;
use Gettext\Merge;
use Gettext\References;
use Gettext\Translation;
use Gettext\Translations;

class PotGenerator
{
    public function __construct(
        private GeneratorInterface $writer,
        private string $localePath = 'languages/',
        private string $defaultLocale = 'en_GB',
    ) {
    }

    public function generate(array $catalogues): int
    {
        $count = 0;
        foreach ($catalogues as $domain => $translations) {
            $this->sortReferences($translations);

            $this->writeFile($translations, $domain);
            $count++;
        }

        return $count;
    }

    protected function writeFile(Translations $translations, string $domain): bool
    {
        $translations->getHeaders()->setLanguage($this->defaultLocale);
        $translations->getHeaders()->set('POT-Creation-Date', (new DateTime())->format('c'));

        return $this->writer->generateFile($translations, sprintf('%s%s.pot', $this->localePath, $domain));
    }

    private function sortReferences(Translations &$translations): void
    {
        foreach ($translations->getTranslations() as $translation) {
            $references = $translation->getReferences()->toArray();

            if (count($references) > 1) {
                $translations->addOrMerge(
                    $this->sortTranslationReferences($references, $translation),
                    Merge::REFERENCES_THEIRS
                );
            }
        }
    }

    /**
     * @param References[] $references
     * @param Translation $translation
     * @return array
     */
    private function sortTranslationReferences(array $references, Translation $translation): Translation
    {
        ksort($references);

        // the new translation is just a container for our sorted references to be put into
        $mergeable = Translation::create($translation->getContext(), $translation->getOriginal());
        foreach ($references as $filename => $lines) {
            if (empty($lines)) {
                $mergeable->getReferences()->add((string)$filename);
                continue;
            }

            foreach ($lines as $line) {
                $mergeable->getReferences()->add((string)$filename, $line);
            }
        }

        return $mergeable;
    }
}
