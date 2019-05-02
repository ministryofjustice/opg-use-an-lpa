<?php

declare(strict_types=1);

return [

    'aws' => [
        'region'    => 'eu-west-1',
        'version'   => 'latest',

        'Kms' => [
            'endpoint' => 'http://kms:8080',
        ],

        'SecretsManager' => [
            'endpoint' => getenv('AWS_ENDPOINT_SECRETS_MANAGER') ?: null,
        ],
    ],

    'session' => [
        'key' => [
            'name' => getenv('SECRET_NAME_SESSION') ?: null,
            'alias' => getenv('KMS_SESSION_CMK_ALIAS') ?: null,
        ],
    ],

];
