<?php

declare(strict_types=1);

return [
    Laminas\ConfigAggregator\ConfigAggregator::ENABLE_CACHE => false,
    'debug' => false,

    'dependencies' => [
        'factories' => [
            \Http\Adapter\Guzzle6\Client::class => \BehatTest\Http\Adapter\Guzzle6\TestClientFactory::class,

            \Aws\Sdk::class => \BehatTest\Common\Service\Aws\SdkFactory::class,

            \Mezzio\Middleware\ErrorResponseGenerator::class => \Mezzio\Container\ErrorResponseGeneratorFactory::class,
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

    'feature_flags' => [
        'use_older_lpa_journey'                                               => true,
        'delete_lpa_feature'                                                  => true,
        'allow_older_lpas'                                                    => true,
        'save_older_lpa_requests'                                             => true,
        'dont_send_lpas_registered_after_sep_2019_to_cleansing_team' => true,
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
        'cookie_secure' => true
    ],

    'ratelimits' => [
        'viewer_code_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'memory',
                    'options' => [
                        'memory_limit' => '96M',
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
        ],
        'actor_code_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'memory',
                    'options' => [
                        'memory_limit' => '96M',
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
        ],
        'actor_login_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'memory',
                    'options' => [
                        'memory_limit' => '96M',
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
    ],

    'whoops' => new \Laminas\Stdlib\ArrayUtils\MergeRemoveKey()
];
