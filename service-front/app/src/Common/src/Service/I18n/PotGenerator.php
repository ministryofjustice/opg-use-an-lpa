<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use DateTime;
use Gettext\Generator\GeneratorInterface;
use Gettext\Translations;

class PotGenerator
{
    public function __construct(
        private GeneratorInterface $writer,
        private string $localePath = 'languages/',
        private string $defaultLocale = 'en_GB',
    ) {

        $this->catalogues = [];
    }

    public function generate(array $catalogues): int
    {
        $count = 0;
        foreach ($catalogues as $domain => $translations) {
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
}
