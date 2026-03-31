<?php

declare(strict_types=1);

use Laminas\Cache\Storage\Adapter\Apcu;

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
    'cache'                => [
        'one-login'      => [
            'adapter' => Apcu::class,
            'options' => [
                'ttl'       => 60,
                'namespace' => 'oneLogin',
            ],
        ],
        'system-message' => [
            'adapter' => Apcu::class,
            'options' => [
                'ttl'       => 300,
                'namespace' => 'systemMessage',
            ],
        ],
        'lpa-data-store' => [
            'adapter' => Apcu::class,
            'options' => [
                'ttl'       => 3600,
                'namespace' => 'lpaDataStoreSecretManager',
            ],
        ],
    ],
];
