<?php

declare(strict_types=1);

return [
    'version'              => getenv('CONTAINER_VERSION') ?: 'dev',
    'environment_name'     => getenv('ENVIRONMENT_NAME') ?: '',
    'sirius_api'           => [
        'endpoint' => getenv('SIRIUS_API_ENDPOINT') ?: null,
    ],
    'lpa_data_store_api'   => [
        'endpoint' => getenv('LPA_DATA_STORE_API_ENDPOINT') ?: null,
    ],
    'codes_api'            => [
        'endpoint'          => getenv('LPA_CODES_API_ENDPOINT') ?: null,
        'static_auth_token' => getenv('LPA_CODES_STATIC_AUTH_TOKEN') ?: null,
    ],
    'iap_images_api'       => [
        'endpoint' => getenv('IAP_IMAGES_API_ENDPOINT') ?: null,
    ],
    'one_login'            => [
        'client_id'     => getenv('ONE_LOGIN_CLIENT_ID') ?: null,
        'discovery_url' => getenv('ONE_LOGIN_DISCOVERY_URL') ?: null,
    ],
    'eventbridge_bus_name' => getenv('EVENTBRIDGE_BUS_NAME') ?: 'default',
    'aws'                  => [
        'region'         => getenv('AWS_REGION') ?: 'eu-west-1',
        'version'        => 'latest',
        'ApiGateway'     => [
            'endpoint_region' => getenv('API_GATEWAY_REGION') ?: 'eu-west-1',
        ],
        'DynamoDb'       => [
            'endpoint' => getenv('AWS_ENDPOINT_DYNAMODB') ?: null,
        ],
        'EventBridge'    => [
            'endpoint' => getenv('AWS_ENDPOINT_EVENTBRIDGE') ?: null,
        ],
        'SecretsManager' => [
            'endpoint' => getenv('AWS_ENDPOINT_SECRETSMANAGER') ?: null,
        ],
        'Ssm'            => [
            'endpoint' => getenv('AWS_ENDPOINT_SSM') ?: null,
        ],
    ],
    'repositories'         => [
        'dynamodb' => [
            'actor-codes-table'     => getenv('DYNAMODB_TABLE_ACTOR_CODES') ?: null,
            'actor-users-table'     => getenv('DYNAMODB_TABLE_ACTOR_USERS') ?: null,
            'viewer-codes-table'    => getenv('DYNAMODB_TABLE_VIEWER_CODES') ?: null,
            'viewer-activity-table' => getenv('DYNAMODB_TABLE_VIEWER_ACTIVITY') ?: null,
            'user-lpa-actor-map'    => getenv('DYNAMODB_TABLE_USER_LPA_ACTOR_MAP') ?: null,
        ],
    ],
    'notify'               => [
        'api' => [
            'key' => getenv('NOTIFY_API_KEY') ?: null,
        ],
    ],
    'symfony_cache'        => [
        'namespace' => 'opg-use-an-lpa-api-' . (getenv('ENVIRONMENT_NAME') ?: 'local'),
        'version'   => getenv('CONTAINER_VERSION') ?: 'dev',
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
