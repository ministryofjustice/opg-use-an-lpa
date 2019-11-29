<?php

declare(strict_types=1);

use Zend\ConfigAggregator\ConfigAggregator;

return [
    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'dependencies' => [
        'factories' => [
            GuzzleHttp\HandlerStack::class => BehatTest\GuzzleHttp\MockHandlerStackFactory::class,
            Http\Adapter\Guzzle6\Client::class => BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,

            Aws\Sdk::class => BehatTest\Common\Service\Aws\SdkFactory::class,
        ],
    ],

    'aws' => [
        'debug'   => true,

        'region'  => 'eu-west-1',
        'version' => 'latest',

        'DynamoDb' => [
            'endpoint' => 'https://dynamodb',
        ],
    ],

    'repositories' => [
        'dynamodb' => [
            'actor-codes-table' => 'actor-codes',
            'actor-users-table' => 'actor-users',
            'viewer-codes-table' => 'viewer-codes',
            'viewer-activity-table' => 'viewer-activity',
            'user-lpa-actor-map' => 'user-actor-lpa-map',
        ]
    ],

    'sirius_api' => [
        'endpoint' => 'https://sirius/v1/use-an-lpa',
    ],
];
