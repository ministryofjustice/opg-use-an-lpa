<?php

return [
    'dependencies' => [
        'factories' => [
            // Logger using the default keys.
            \Psr\Log\LoggerInterface::class => \Blazon\PSR11MonoLog\MonologFactory::class,
        ]
    ],

    'monolog' => [
        'handlers' => [
            // Log to stdout
            'default' => [
                'type' => 'stream',
                'options' => [
                    'stream' => 'php://stdout',
                    'level' => getenv('LOGGING_LEVEL') ?: \Monolog\Logger::NOTICE
                ],
                'formatter' => 'jsonFormatter',
                'processors' => [
                    'psrLogProcessor',
                    'requestTracingProcessor',
                    'introspectionProcessor'
                ],
            ],
        ],
        'formatters' => [
            'jsonFormatter' => [
                'type' => 'json',
                'options' => [],
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
            'introspectionProcessor' => [
                'type' => 'introspection'
            ]
        ],
    ],
];
