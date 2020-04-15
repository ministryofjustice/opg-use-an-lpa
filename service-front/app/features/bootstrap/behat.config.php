<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

return [
    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'dependencies' => [
        'factories' => [
            \Http\Adapter\Guzzle6\Client::class => \BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,

            \Aws\Sdk::class => \BehatTest\Common\Service\Aws\SdkFactory::class,
        ],
    ],

    'api' => [
        'uri' => 'http://localhost',
    ],

    'pdf' => [
        'uri' => 'http://pdf-service',
    ],

    'aws' => [
        //'debug'   => true,
    ],

    'notify' => [
        'api' => [
            'key' => 'not_a_real_key-22996155-4e04-42d0-8d1a-d1d3998e2149-be30242e-049d-4039-b43e-14aa8a6a76a4',
        ],
    ],

    'monolog' => [
        'handlers' => [
            'default' => [ // default configuration in normal operation
                'type' => 'test',
                'processors' => [
                    'psrLogProcessor',
                    'requestTracingProcessor'
                ],
            ],
        ],
        'processors' => [
            'psrLogProcessor' => [
                'type' => 'psrLogMessage',
                'options' => [], // No options
            ],
            'requestTracingProcessor' => [
                'type' => \Common\Service\Log\RequestTracingLogProcessorFactory::class,
                'options' => [], // No options
            ],
        ],
    ],

    'session' => [
        'key' => [
            // KMS alias to use for data key generation.
            'alias' => 'alias/viewer-sessions-cmk-alias',
        ],
    ],

    'ratelimits' => [
        'viewer_code_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'apcu',
                    'options' => [
                        'ttl' => 60,
                        'server' => new \Laminas\Stdlib\ArrayUtils\MergeRemoveKey(),
                        'lib_options' => new \Laminas\Stdlib\ArrayUtils\MergeRemoveKey()
                    ],
                ],
            ],
            'options' => [
                'interval' => 60,
                'requests_per_interval' => 4
            ]
        ]
    ]
];
