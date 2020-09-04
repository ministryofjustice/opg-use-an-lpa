<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    \Mezzio\Authentication\Session\ConfigProvider::class,
    \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    \Mezzio\Authentication\ConfigProvider::class,
    \Mezzio\Session\ConfigProvider::class,
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Mezzio\Csrf\ConfigProvider::class,
    \Mezzio\Twig\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,

    // App module(s) config
    Common\ConfigProvider::class,
    Actor\ConfigProvider::class,
    Viewer\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/../../config/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/behat.config.php'),
], null);

return $aggregator->getMergedConfig();
