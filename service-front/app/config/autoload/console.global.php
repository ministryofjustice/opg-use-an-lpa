<?php

declare(strict_types=1);

use Common\Command\TranslationUpdateCommand;
use Common\Command\TranslationUpdateCommandFactory;

return [
    'dependencies' => [
        'invokables' => [],
        'factories'  => [
            TranslationUpdateCommand::class => TranslationUpdateCommandFactory::class,
        ],
    ],
    'console'      => [
        'commands' => [
            TranslationUpdateCommand::class,
        ],
    ],
];
