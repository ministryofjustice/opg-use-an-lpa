<?php

declare(strict_types=1);

return [

    'session' => [
        'key' => [
            'name' => getenv('SECRET_NAME_SESSION') ?: null,
        ],
    ],

];
