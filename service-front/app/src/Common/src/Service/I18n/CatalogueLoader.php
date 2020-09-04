<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use Gettext\Loader\LoaderInterface;
use Gettext\Translations;
use Symfony\Component\Finder\Finder;

class CatalogueLoader
{
    private Finder $fileFinder;
    private LoaderInterface $loader;

    public function __construct(LoaderInterface $loader, Finder $fileFinder)
    {
        $this->loader = $loader;
        $this->fileFinder = $fileFinder;
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
        return $this->fileFinder->files()->name('*.pot')->in($directory);
    }
}
