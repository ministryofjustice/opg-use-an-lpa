<?php

declare(strict_types=1);

return [
    'version'              => get_defaulted_env('CONTAINER_VERSION', 'dev'),
    'environment_name'     => get_defaulted_env('ENVIRONMENT_NAME', ''),
    'sirius_api'           => [
        'endpoint' => get_defaulted_env('SIRIUS_API_ENDPOINT'),
    ],
    'lpa_data_store_api'   => [
        'endpoint' => get_defaulted_env('LPA_DATA_STORE_API_ENDPOINT'),
    ],
    'codes_api'            => [
        'endpoint'          => get_defaulted_env('LPA_CODES_API_ENDPOINT'),
        'static_auth_token' => get_defaulted_env('LPA_CODES_STATIC_AUTH_TOKEN'),
    ],
    'iap_images_api'       => [
        'endpoint' => get_defaulted_env('IAP_IMAGES_API_ENDPOINT'),
    ],
    'one_login'            => [
        'client_id'     => get_defaulted_env('ONE_LOGIN_CLIENT_ID'),
        'discovery_url' => get_defaulted_env('ONE_LOGIN_DISCOVERY_URL'),
    ],
    'eventbridge_bus_name' => get_defaulted_env('EVENTBRIDGE_BUS_NAME', 'default'),
    'aws'                  => [
        'region'         => get_defaulted_env('AWS_REGION', 'eu-west-1'),
        'version'        => 'latest',
        'ApiGateway'     => [
            'endpoint_region' => get_defaulted_env('API_GATEWAY_REGION', 'eu-west-1'),
        ],
        'DynamoDb'       => [
            'endpoint' => get_defaulted_env('AWS_ENDPOINT_DYNAMODB'),
        ],
        'EventBridge'    => [
            'endpoint' => get_defaulted_env('AWS_ENDPOINT_EVENTBRIDGE'),
        ],
        'SecretsManager' => [
            'endpoint' => get_defaulted_env('AWS_ENDPOINT_SECRETSMANAGER'),
        ],
        'Ssm'            => [
            'endpoint' => get_defaulted_env('AWS_ENDPOINT_SSM'),
        ],
    ],
    'repositories'         => [
        'dynamodb' => [
            'actor-codes-table'     => get_defaulted_env('DYNAMODB_TABLE_ACTOR_CODES'),
            'actor-users-table'     => get_defaulted_env('DYNAMODB_TABLE_ACTOR_USERS'),
            'viewer-codes-table'    => get_defaulted_env('DYNAMODB_TABLE_VIEWER_CODES'),
            'viewer-activity-table' => get_defaulted_env('DYNAMODB_TABLE_VIEWER_ACTIVITY'),
            'user-lpa-actor-map'    => get_defaulted_env('DYNAMODB_TABLE_USER_LPA_ACTOR_MAP'),
        ],
    ],
    'notify'               => [
        'api' => [
            'key' => get_defaulted_env('NOTIFY_API_KEY'),
        ],
    ],
    'symfony_cache'        => [
        'namespace' => 'opg-use-an-lpa-api-' . get_defaulted_env('ENVIRONMENT_NAME', 'local'),
        'version'   => get_defaulted_env('CONTAINER_VERSION', 'dev'),
        'pools'     => [
            'cache.request'        => [
                'adapter'          => 'array',
                'default_lifetime' => 0,
            ],
            'cache.app'            => [
                'adapter'          => 'apcu',
                'default_lifetime' => 0,
            ],
            'cache.one-login'      => [
                'adapter'          => 'apcu',
                'default_lifetime' => 60,
            ],
            'cache.system-message' => [
                'adapter'          => 'apcu',
                'default_lifetime' => 300,
            ],
            'cache.lpa-data-store' => [
                'adapter'          => 'apcu',
                'default_lifetime' => 3600,
            ],
        ],
    ],
];
