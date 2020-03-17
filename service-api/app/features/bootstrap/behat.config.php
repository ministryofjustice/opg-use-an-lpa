<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

return [
    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'dependencies' => [
        'factories' => [
            Http\Adapter\Guzzle6\Client::class => BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,
            GuzzleHttp\Client::class => BehatTest\GuzzleHttp\TestClientFactory::class,

            Aws\Sdk::class => BehatTest\Common\Service\Aws\SdkFactory::class,
        ],
    ],

    'aws' => [
        'region' => 'eu-west-1',
        'version' => 'latest',

        'DynamoDb' => [
            'endpoint' => 'https://dynamodb',
        ],
    ],

    'monolog' => [
        'handlers' => [
            'default' => [ // default configuration in normal operation
                'type' => 'test',
                'processors' => [
                    'psrLogProcessor',
                    'requestTracingProcessor',
                ],
            ],
        ],
        'processors' => [
            'psrLogProcessor' => [
                'type' => 'psrLogMessage',
                'options' => [], // No options
            ],
            'requestTracingProcessor' => [
                'type' => \App\Service\Log\RequestTracingLogProcessorFactory::class,
                'options' => [], // No options
            ],
        ],
    ],

    'repositories' => [
        'dynamodb' => [
            'actor-codes-table' => 'actor-codes',
            'actor-users-table' => 'actor-users',
            'viewer-codes-table' => 'viewer-codes',
            'viewer-activity-table' => 'viewer-activity',
            'user-lpa-actor-map' => 'user-actor-lpa-map',
        ],
    ],

    'sirius_api' => [
        'endpoint' => 'https://sirius',
    ],
];
