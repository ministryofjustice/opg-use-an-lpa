<?php

declare(strict_types=1);

namespace Common\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class TranslationUpdateCommand extends Command
{
    public const DEFAULT_LOCALE = 'en_GB';

    private ExtractorInterface $extractor;
    private LoaderInterface $loader;
    private array $viewsPaths;
    private TranslationWriterInterface $writer;

    public function __construct(
        TranslationWriterInterface $writer,
        LoaderInterface $loader,
        ExtractorInterface $extractor,
        array $viewsPaths = []
    ) {
        parent::__construct();

        $this->writer = $writer;
        $this->loader = $loader;
        $this->extractor = $extractor;
        $this->viewsPaths = $viewsPaths;
    }

    protected function configure(): void
    {
        $this
            ->setName('translation:update')
            ->setDescription(
                'Parses application Twig template files for translatable strings and writes ' .
                'out translation template files.'
            )
            ->addOption(
                'clean',
                'c',
                InputOption::VALUE_NONE,
                'Write out the translation file anew. Do not attempt to merge with existing data.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $io->getErrorStyle();

        if (true === $input->getOption('clean')) {
            $io->caution('Clean will replace your translation template file with one generated from your Twig files.');
            if (!$io->confirm('Continue with this action?', false)) {
                return Command::SUCCESS;
            }
        }

        // load any messages from templates
        $extractedCatalogue = new MessageCatalogue(self::DEFAULT_LOCALE);
        $io->comment('Parsing templates...');
        foreach ($this->viewsPaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $this->extractor->extract($path, $extractedCatalogue);
            }
        }

        // load any existing messages from the translation files
        $currentCatalogue = new MessageCatalogue(self::DEFAULT_LOCALE);
        $io->comment('Loading translation files...');
        $currentCatalogue->addCatalogue(
            $this->loader->load(
                sprintf('languages/%s.pot', self::DEFAULT_LOCALE),
                self::DEFAULT_LOCALE
            )
        );

        // process catalogues
        $operation = $input->getOption('clean')
            ? new TargetOperation($currentCatalogue, $extractedCatalogue)
            : new MergeOperation($currentCatalogue, $extractedCatalogue);

        $io->comment('Writing files...');

        $this->writer->write(
            $operation->getResult(),
            'po',
            [
                'path' => 'languages/',
                'default_locale' => self::DEFAULT_LOCALE,
            ]
        );

        $io->success('Translation files were successfully updated');

        return 0;
    }
}
