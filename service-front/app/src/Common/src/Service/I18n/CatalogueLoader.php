<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Gettext\Loader\PoLoader;
use Gettext\Translations;
use Symfony\Component\Finder\Finder;

class CatalogueLoader
{
    private PoLoader $loader;

    public function __construct(PoLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Reads all '.pot' files from the given directory and loads them into a domain keyed catalogue of
     * translations.
     *
     * @param string $directory
     * @return Translations[]
     */
    public function loadByDirectory(string $directory): array
    {
        $catalogues = [];

        $files = $this->findPotFiles($directory);

        foreach ($files as $file) {
            $translations = $this->loader->loadFile($file->getRealPath());
            $catalogues[$translations->getDomain()] = $translations;
        }

        return $catalogues;
    }

    private function findPotFiles(string $directory): Finder
    {
        $finder = new Finder();

        return $finder->files()->name('*.pot')->in($directory);
    }
}
