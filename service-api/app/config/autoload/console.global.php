<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'invokables' => [
        ],

        'factories' => [
            App\Command\ActorCodeCreationCommand::class => App\Command\ActorCodeCreationCommandFactory::class
        ],
    ],

    'console' => [
        'commands' => [
            App\Command\ActorCodeCreationCommand::class
        ],
    ],
];