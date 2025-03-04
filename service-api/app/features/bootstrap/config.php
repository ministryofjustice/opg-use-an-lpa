<?php

declare(strict_types=1);

use App\Service\Log\RequestTracingLogProcessorFactory;
use Aws\Sdk;
use BehatTest\Common\Service\Aws\SdkFactory;
use BehatTest\GuzzleHttp\TestClientFactory;
use Elie\PHPDI\Config\ConfigInterface;
use Laminas\ConfigAggregator\ConfigAggregator;
use Psr\Http\Client\ClientInterface;

return [
    'debug'                                  => true,
    ConfigAggregator::ENABLE_CACHE           => false,
    ConfigInterface::ENABLE_CACHE_DEFINITION => false,
    'dependencies'                           => [
        'factories' => [
            ClientInterface::class => TestClientFactory::class,
            Sdk::class             => SdkFactory::class,
        ],
    ],
    'aws'                                    => [
        'region'   => getenv('AWS_REGION') ?: 'eu-west-1',
        'version'  => 'latest',
        'DynamoDb' => [
            'endpoint' => 'https://dynamodb',
        ],
    ],
    'monolog'                                => [
        'handlers'   => [
            'default' => [ // default configuration in normal operation
                'type'       => 'test',
                'processors' => [
                    'psrLogProcessor',
                    'requestTracingProcessor',
                ],
            ],
        ],
        'processors' => [
            'psrLogProcessor'         => [
                'type'    => 'psrLogMessage',
                'options' => [], // No options
            ],
            'requestTracingProcessor' => [
                'type'    => RequestTracingLogProcessorFactory::class,
                'options' => [], // No options
            ],
        ],
    ],
    'repositories'                           => [
        'dynamodb' => [
            'actor-codes-table'     => 'actor-codes',
            'actor-users-table'     => 'actor-users',
            'viewer-codes-table'    => 'viewer-codes',
            'viewer-activity-table' => 'viewer-activity',
            'user-lpa-actor-map'    => 'user-actor-lpa-map',
        ],
    ],
    'sirius_api'                             => [
        'endpoint' => 'http://api-gateway-pact-mock',
    ],
    'lpa_data_store_api'                     => [
        'endpoint' => 'http://lpa-data-store-pact-mock',
    ],
    'codes_api'                              => [
        'endpoint'          => 'http://lpa-codes-pact-mock',
        'static_auth_token' => getenv('LPA_CODES_STATIC_AUTH_TOKEN') ?: null,
    ],
    'iap_images_api'                         => [
        'endpoint' => 'http://iap-images-mock',
    ],
    'one_login'                              => [
        'client_id'       => 'client-id',
        'discovery_url'   => 'http://one-login-mock/.well-known/openid-configuration',
        'identity_issuer' => 'http://identity.one-login-mock/',
    ],
    'feature_flags'                          => [
        'support_datastore_lpas' => false,
        'paper_verification'     => false,
    ],
];
