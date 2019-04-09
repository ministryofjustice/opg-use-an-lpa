<?php

declare(strict_types=1);

return [

    'aws' => [
        'region'    => 'eu-west-2',
        'version'   => 'latest',

        'SecretsManager' => [
            'endpoint' => getenv('AWS_ENDPOINT_SECRETS_MANAGER') ?: null,
        ],
    ],

    'session' => [
        'key' => [
            'name' => getenv('SECRET_NAME_SESSION') ?: null,
        ],
    ],

];
