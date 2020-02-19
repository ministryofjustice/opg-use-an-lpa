<?php

return [
    'dependencies' => [
        'factories' => [
            // Logger using the default keys.
            \Psr\Log\LoggerInterface::class => \WShafer\PSR11MonoLog\MonologFactory::class,
        ],
    ],

    'monolog' => [
        'handlers' => [
            // Log to stdout
            'default' => [
                'type' => 'stream',
                'options' => [
                    'stream' => 'php://stdout',
                ],
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
];