<?php

declare(strict_types=1);

return [

    'version' => getenv('CONTAINER_VERSION') ?: 'dev',

    'sirius_api' => [
        'endpoint' => getenv('SIRIUS_API_ENDPOINT') ?: null,
    ],

    'codes_api' => [
        'endpoint' => getenv('LPA_CODES_API_ENDPOINT') ?: null,
        'static_auth_token' => getenv('LPA_CODES_STATIC_AUTH_TOKEN') ?: null,
    ],

    'aws' => [
        'region'  => 'eu-west-1',
        'version' => 'latest',

        'DynamoDb' => [
            'endpoint' => getenv('AWS_ENDPOINT_DYNAMODB') ?: null,
        ],
    ],

    'repositories' => [
        'dynamodb' => [
            'actor-codes-table' => getenv('DYNAMODB_TABLE_ACTOR_CODES') ?: null,
            'actor-users-table' => getenv('DYNAMODB_TABLE_ACTOR_USERS') ?: null,
            'viewer-codes-table' => getenv('DYNAMODB_TABLE_VIEWER_CODES') ?: null,
            'viewer-activity-table' => getenv('DYNAMODB_TABLE_VIEWER_ACTIVITY') ?: null,
            'user-lpa-actor-map' => getenv('DYNAMODB_TABLE_USER_LPA_ACTOR_MAP') ?: null,
        ]
    ],

];
