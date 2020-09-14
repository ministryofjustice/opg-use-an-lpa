<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Common\Service\I18n\CatalogueLoader;
use Gettext\Loader\LoaderInterface;
use Gettext\Translations;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CatalogueLoaderTest extends TestCase
{
    public function getMockFileFinder(array $files): ObjectProphecy
    {
        $finderProphecy = $this->prophesize(Finder::class);
        $finderProphecy->files()->willReturn($finderProphecy->reveal());
        $finderProphecy->name('*.pot')->willReturn($finderProphecy->reveal());
        $finderProphecy->in('/')->willReturn($finderProphecy->reveal());
        $finderProphecy
            ->getIterator()
            ->willReturn(new \ArrayObject($files));

        return $finderProphecy;
    }

    public function getMockFile(string $path): ObjectProphecy
    {
        $fileProphecy = $this->prophesize(SplFileInfo::class);
        $fileProphecy->getRealPath()->willReturn($path);

        return $fileProphecy;
    }

    public function getMockTranslations(string $domain): ObjectProphecy
    {
        $translations = $this->prophesize(Translations::class);
        $translations->getDomain()->willReturn($domain);

        return $translations;
    }

    /** @test */
    public function it_will_load_all_pot_files_in_a_folder(): void
    {
        $poloaderProphecy = $this->prophesize(LoaderInterface::class);
        $poloaderProphecy
            ->loadFile('/messages.pot')
            ->willReturn($this->getMockTranslations('messages'));
        $poloaderProphecy
            ->loadFile('/errors.pot')
            ->willReturn($this->getMockTranslations('errors'));

        $files = [
            $this->getMockFile('/messages.pot')->reveal(),
            $this->getMockFile('/errors.pot')->reveal(),
        ];

        $loader = new CatalogueLoader(
            $poloaderProphecy->reveal(),
            $this->getMockFileFinder($files)->reveal()
        );

        $translations = $loader->loadByDirectory('/');

        $this->assertArrayHasKey('messages', $translations);
        $this->assertArrayHasKey('errors', $translations);
    }
}
