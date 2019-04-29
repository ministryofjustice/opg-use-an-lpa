<?php

declare(strict_types=1);

// Configuration details found here
// https://docs.zendframework.com/zend-expressive/v3/features/template/twig/

return [
    'twig' => [
        'cache_dir' => '/tmp/twig_cache',
        'assets_url' => '/',
        'assets_version' => '1',
        'timezone' => 'Europe/London',
        'optimizations' => -1,
        'autoescape' => 'html',
        'auto_reload' => true,
    ],
];