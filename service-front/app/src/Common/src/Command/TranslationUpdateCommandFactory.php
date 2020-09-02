<?php

declare(strict_types=1);

namespace Common\Command;

use Common\Service\I18n\PotGenerator;
use Common\Service\I18n\TwigCatalogueExtractor;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactory
{
    public function __invoke(ContainerInterface $container): TranslationUpdateCommand
    {
        return new TranslationUpdateCommand(
            $container->get(TwigCatalogueExtractor::class),
            $container->get(PotGenerator::class),
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
