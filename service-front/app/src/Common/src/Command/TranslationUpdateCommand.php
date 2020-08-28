<?php

declare(strict_types=1);

namespace Common\Command;

use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TranslationUpdateCommand extends Command
{
    public const DEFAULT_LOCALE = 'en_GB';

    private TwigCatalogueExtractor $extractorService;
    private PotGenerator $writer;
    private array $viewsPaths;

    public function __construct(
        TwigCatalogueExtractor $extractorService,
        PotGenerator $writer,
        array $viewsPaths = []
    ) {
        $this->extractorService = $extractorService;
        $this->writer = $writer;
        $this->viewsPaths = $viewsPaths;

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

        $io->section('Parsing templates...');
        $catalogues = $this->extractorService->extract($this->viewsPaths);
        $io->text(sprintf('Found %d domains', count($catalogues)));

        $io->section('Generating POT file\s...');
        $count = $this->writer->generate($catalogues);
        $io->text(sprintf('Created %d POT file/s', $count));

        $io->success('Translation files were successfully updated');

        return 0;
    }
}
