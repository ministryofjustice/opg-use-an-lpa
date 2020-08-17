<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'invokables' => [
        ],

        'factories' => [
            Common\Command\TranslationUpdateCommand::class => Common\Command\TranslationUpdateCommandFactory::class
        ],
    ],

    'console' => [
        'commands' => [
            Common\Command\TranslationUpdateCommand::class
        ],
    ],
];
