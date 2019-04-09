<?php

declare(strict_types=1);

return [

    'aws' => [
        'region'    => 'eu-west-2',
        'version'   => 'latest',

        /*
        'credentials' => [
            'key'    => 'my-access-key-id',
            'secret' => 'my-secret-access-key',
        ],
        */

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
