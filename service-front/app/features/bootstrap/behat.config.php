<?php

declare(strict_types=1);

use Aws\Sdk;
use BehatTest\Common\Service\ApiClient\TestGuzzleClientFactory;
use BehatTest\Common\Service\Aws\SdkFactory;
use Common\Service\Log\RequestTracingLogProcessorFactory;
use Elie\PHPDI\Config\ConfigInterface;
use GuzzleHttp\Client;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Stdlib\ArrayUtils\MergeRemoveKey;
use Mezzio\Container\ErrorResponseGeneratorFactory;
use Mezzio\Middleware\ErrorResponseGenerator;

return [
    ConfigAggregator::ENABLE_CACHE           => false,
    ConfigInterface::ENABLE_CACHE_DEFINITION => false,
    'debug'                                  => false,
    'dependencies'                           => [
        'factories' => [
            Client::class                 => TestGuzzleClientFactory::class,
            Sdk::class                    => SdkFactory::class,
            ErrorResponseGenerator::class => ErrorResponseGeneratorFactory::class,
        ],
    ],
    'api'                                    => [
        'uri' => 'http://localhost',
    ],
    'pdf'                                    => [
        'uri' => 'http://pdf-service',
    ],
    'aws'                                    => [
        //'debug'   => true,
    ],
    'feature_flags'                          => [
        'delete_lpa_feature'           => true,
        'instructions_and_preferences' => true,
        'allow_gov_one_login'          => true,
        'support_datastore_lpas'       => false,
    ],
    'notify'                                 => [
        'api' => [
            'key' => 'not_a_real_key-22996155-4e04-42d0-8d1a-d1d3998e2149-be30242e-049d-4039-b43e-14aa8a6a76a4',
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
    'session'                                => [
        'key'           => [
            // KMS alias to use for data key generation.
            'alias' => 'alias/viewer-sessions-cmk-alias',
        ],
        'cookie_secure' => true,
    ],
    'ratelimits'                             => [
        'viewer_code_failure' => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => Memory::class,
                'options' => [
                    'memory_limit'  => '96M',
                    'ttl'           => 60,
                    'persistent_id' => new MergeRemoveKey(),
                    'server'        => new MergeRemoveKey(),
                    'lib_options'   => new MergeRemoveKey(),
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_code_failure'  => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => Memory::class,
                'options' => [
                    'memory_limit'  => '96M',
                    'ttl'           => 60,
                    'persistent_id' => new MergeRemoveKey(),
                    'server'        => new MergeRemoveKey(),
                    'lib_options'   => new MergeRemoveKey(),
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_login_failure' => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => Memory::class,
                'options' => [
                    'memory_limit'  => '96M',
                    'ttl'           => 60,
                    'persistent_id' => new MergeRemoveKey(),
                    'server'        => new MergeRemoveKey(),
                    'lib_options'   => new MergeRemoveKey(),
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
    ],
    'twig'                                   => [
        'strict_variables' => true,
    ],
    'whoops'                                 => new MergeRemoveKey(),
];
