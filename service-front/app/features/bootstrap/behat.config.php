<?php

declare(strict_types=1);

use Aws\Sdk;
use BehatTest\Common\Service\ApiClient\TestGuzzleClientFactory;
use BehatTest\Common\Service\Aws\SdkFactory;
use Common\Service\Log\RequestTracingLogProcessorFactory;
use Elie\PHPDI\Config\ConfigInterface;
use GuzzleHttp\Client;
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
        'support_datastore_lpas' => false,
        'paper_verification'     => false,
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
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_code_failure'  => [
            'type'    => 'keyed',
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_login_failure' => [
            'type'    => 'keyed',
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'download_lpa'        => [
            'type'    => 'keyed',
            'options' => [
                'interval'              => 15,
                'requests_per_interval' => 2,
            ],
        ],
    ],
    'symfony_cache'                          => [
        'namespace' => 'opg-use-an-lpa-front-test',
        'version'   => 'test',
        'pools'     => [
            'cache.ratelimits.viewer_code_failure' => [
                'adapter'          => 'array',
                'default_lifetime' => 60,
            ],
            'cache.ratelimits.actor_code_failure'  => [
                'adapter'          => 'array',
                'default_lifetime' => 60,
            ],
            'cache.ratelimits.actor_login_failure' => [
                'adapter'          => 'array',
                'default_lifetime' => 60,
            ],
            'cache.ratelimits.download_lpa'        => [
                'adapter'          => 'array',
                'default_lifetime' => 60,
            ],
        ],
    ],
    'twig'                                   => [
        'strict_variables' => true,
    ],
    'whoops'                                 => new MergeRemoveKey(),
];
