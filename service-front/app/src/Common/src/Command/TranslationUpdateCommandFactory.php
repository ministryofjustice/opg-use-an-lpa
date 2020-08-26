<?php

declare(strict_types=1);

namespace Common\Command;

use Acpr\I18n\TwigExtractor;
use Gettext\Generator\PoGenerator;
use Psr\Container\ContainerInterface;

class TranslationUpdateCommandFactory
{
    public function __invoke(ContainerInterface $container): TranslationUpdateCommand
    {
        return new TranslationUpdateCommand(
            $container->get(TwigExtractor::class),
            $container->get(PoGenerator::class),
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
