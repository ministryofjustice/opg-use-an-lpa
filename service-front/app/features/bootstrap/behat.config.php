<?php

declare(strict_types=1);

use Zend\ConfigAggregator\ConfigAggregator;

return [
    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'dependencies' => [
        'aliases' => [
            'guzzle.mockhandler' => GuzzleHttp\Handler\MockHandler::class,
            'notify.spymailer' => Alphagov\Notifications\Client::class
        ],

        'factories' => [
            Http\Adapter\Guzzle6\Client::class => BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,
            GuzzleHttp\HandlerStack::class => BehatTest\GuzzleHttp\MockHandlerStackFactory::class,
        ]
    ],

    'api' => [
        'uri' => 'http://localhost',
    ],
];
