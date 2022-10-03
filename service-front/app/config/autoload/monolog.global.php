<?php

declare(strict_types=1);

use Blazon\PSR11MonoLog\MonologFactory;
use Common\Service\Log\RequestTracingLogProcessorFactory;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            // Logger using the default keys.
            LoggerInterface::class => MonologFactory::class,
        ],
    ],
    'monolog'      => [
        'handlers'   => [
            // Log to stdout
            'default' => [
                'type'       => 'stream',
                'options'    => [
                    'stream' => 'php://stdout',
                    'level'  => getenv('LOGGING_LEVEL') ?: Logger::NOTICE,
                ],
                'formatter'  => 'jsonFormatter',
                'processors' => [
                    'psrLogProcessor',
                    'requestTracingProcessor',
                    'introspectionProcessor',
                ],
            ],
        ],
        'formatters' => [
            'jsonFormatter' => [
                'type'    => 'json',
                'options' => [],
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
            'introspectionProcessor'  => [
                'type' => 'introspection',
            ],
        ],
    ],
];
