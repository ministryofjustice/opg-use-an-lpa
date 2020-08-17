<?php

declare(strict_types=1);

namespace Common\Command;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;

class TranslationUpdateCommandFactory
{
    public function __invoke(ContainerInterface $container): TranslationUpdateCommand
    {
        $dumper = $container->get(PoFileDumper::class);
        $dumper->setRelativePathTemplate('%locale%.pot');

        $writer = $container->get(TranslationWriter::class);
        $writer->addDumper('po', $dumper);

        $loader = $container->get(PoFileLoader::class);

        $extractor = $container->get(ChainExtractor::class);
        $extractor->addExtractor('twig', $container->get(TwigExtractor::class));

        return new TranslationUpdateCommand(
            $writer,
            $loader,
            $extractor,
            [
                'src/Actor/templates/actor/',
                'src/Actor/templates/actor/partials/',
                'src/Common/templates/error/',
                'src/Common/templates/layouts/',
                'src/Common/templates/partials/',
                'src/Viewer/templates/viewer/'
            ]
        );
    }
}
