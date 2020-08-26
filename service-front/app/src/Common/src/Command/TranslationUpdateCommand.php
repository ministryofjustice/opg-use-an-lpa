<?php

declare(strict_types=1);

namespace Common\Command;

use Acpr\I18n\ExtractorInterface;
use DateTime;
use Gettext\Generator\GeneratorInterface;
use Gettext\Translations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TranslationUpdateCommand extends Command
{
    public const DEFAULT_LOCALE = 'en_GB';

    private ExtractorInterface $extractor;
    private GeneratorInterface $writer;
    private array $viewsPaths;

    public function __construct(
        ExtractorInterface $extractor,
        GeneratorInterface $writer,
        array $viewsPaths = []
    ) {
        parent::__construct();

        $this->extractor = $extractor;
        $this->writer = $writer;
        $this->viewsPaths = $viewsPaths;
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
        $errorIo = $io->getErrorStyle();

        /** @var Translations[] $catalogues */
        $catalogues = [];

        $io->comment('Parsing templates...');
        foreach ($this->viewsPaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $translations = $this->extractor->extract($path);
            }

            array_walk($translations, function (Translations $translations, string $domain) use (&$catalogues) {
                if (in_array($domain, array_keys($catalogues))) {
                    $catalogues[$domain] = $catalogues[$domain]->mergeWith($translations);
                } else {
                    $catalogues[$domain] = $translations;
                }
                return true;
            });
        }

        $io->comment('Writing files...');
        foreach ($catalogues as $domain => $translations) {
            $translations->getHeaders()->setLanguage('en_GB'); // our template is in english
            $translations->getHeaders()->set('POT-Creation-Date', (new DateTime())->format('c'));

            $this->writer->generateFile($translations, sprintf('languages/%s.pot', $domain));
        }

        $io->success('Translation files were successfully updated');

        return 0;
    }
}
