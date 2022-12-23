<?php

declare(strict_types=1);

use Twig\Extension\DebugExtension;

// Configuration details found here
// https://docs.zendframework.com/zend-expressive/v3/features/template/twig/

return [
    'twig' => [
        'cache_dir'      => '/tmp/twig_cache',
        'assets_url'     => '/',
        'assets_version' => getenv('CONTAINER_VERSION') ?: 'dev',
        'timezone'       => 'Europe/London',
        'optimizations'  => -1,
        'autoescape'     => 'html',
        'auto_reload'    => true,
        'extensions'     => [
            // extension service names or instances
            DebugExtension::class,
        ],
    ],
];
