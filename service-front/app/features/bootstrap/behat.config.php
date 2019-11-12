<?php

declare(strict_types=1);

use Zend\ConfigAggregator\ConfigAggregator;

return [
    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'dependencies' => [
        'aliases' => [
            'guzzle.mockhandler' => GuzzleHttp\Handler\MockHandler::class,
        ],

        'factories' => [
            Http\Adapter\Guzzle6\Client::class => BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,
            GuzzleHttp\HandlerStack::class => BehatTest\GuzzleHttp\MockHandlerStackFactory::class,
        ]
    ],

    'api' => [
        'uri' => 'http://localhost',
    ],

    'notify' => [
        'api' => [
            'key' => 'not_a_real_key-22996155-4e04-42d0-8d1a-d1d3998e2149-be30242e-049d-4039-b43e-14aa8a6a76a4',
        ],
    ],
];
