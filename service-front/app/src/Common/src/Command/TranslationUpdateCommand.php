<?php

declare(strict_types=1);

namespace Common\Command;

use Common\Service\I18n\CatalogueLoader;
use Common\Service\I18n\Extractors\PhpFactory;
use Common\Service\I18n\Extractors\TwigFactory;
use Common\Service\I18n\PotGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TranslationUpdateCommand extends Command
{
    public const DEFAULT_LOCALE = 'en_GB';

    public function __construct(
        private TwigFactory $twigExtractorFactory,
        private PhpFactory $phpExtractorFactory,
        private CatalogueLoader $loader,
        private PotGenerator $writer,
        private array $viewsPaths = [],
        private array $phpPaths = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('translation:update')
            ->setDescription(
                'Parses application Twig template files for translatable strings and writes ' .
                'out translation template files.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Loading existing catalogues...');
        $existing = $this->loader->loadByDirectory('languages/');
        $io->text(sprintf('Found %d domains', count($existing)));

        $io->section('Parsing templates...');
        $extractorService = ($this->twigExtractorFactory)();
        $twigCatalogues   = $extractorService->extract($this->viewsPaths);
        $io->text(sprintf('Found %d domains', count($twigCatalogues)));

        $io->section('Parsing php...');
        $extractorService = ($this->phpExtractorFactory)();
        $phpCatalogues    = $extractorService->extract($this->phpPaths);
        $io->text(sprintf('Found %d domains', count($phpCatalogues)));

        $io->section('Merging translation catalogues...');
        $extracted  = $extractorService->mergeCatalogues($twigCatalogues, $phpCatalogues, 0);
        $catalogues = $extractorService->mergeCatalogues($existing, $extracted);
        $io->text(sprintf('%d domains extracted', count($catalogues)));

        $io->section('Generating POT file\s...');
        $count = $this->writer->generate($catalogues);
        $io->text(sprintf('Created %d POT file/s', $count));

        $io->success('Translation files were successfully updated');

        return 0;
    }
}
