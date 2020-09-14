<?php

declare(strict_types=1);

namespace Common\Command;

use Common\Service\I18n\CatalogueLoader;
use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractorFactory;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactory
{
    public function __invoke(ContainerInterface $container): TranslationUpdateCommand
    {
        return new TranslationUpdateCommand(
            $container->get(TwigCatalogueExtractorFactory::class),
            $container->get(CatalogueLoader::class),
            $container->get(PotGenerator::class),
            [
                'src/Actor/templates/actor/',
                'src/Actor/templates/actor/partials/',
                'src/Common/templates/error/',
                'src/Common/templates/layout/',
                'src/Common/templates/partials/',
                'src/Viewer/templates/viewer/'
            ]
        );
    }
}
