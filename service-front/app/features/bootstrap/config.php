<?php

declare(strict_types=1);

use Actor\ConfigProvider as ActorConfigProvider;
use Common\ConfigProvider as CommonConfigProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use Laminas\HttpHandlerRunner\ConfigProvider as HttpHandlerRunnerConfigProvider;
use Mezzio\Authentication\ConfigProvider as AuthenticationConfigProvider;
use Mezzio\Authentication\Session\ConfigProvider as AuthenticationSessionConfigProvider;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Csrf\ConfigProvider as CsrfConfigProvider;
use Mezzio\Helper\ConfigProvider as HelperConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use Mezzio\Session\ConfigProvider as SessionConfigProvider;
use Mezzio\Twig\ConfigProvider as TwigConfigProvider;
use Viewer\ConfigProvider as ViewerConfigProvider;

$aggregator = new ConfigAggregator([
    AuthenticationSessionConfigProvider::class,
    FastRouteConfigProvider::class,
    HttpHandlerRunnerConfigProvider::class,
    AuthenticationConfigProvider::class,
    SessionConfigProvider::class,
    HelperConfigProvider::class,
    RouterConfigProvider::class,
    CsrfConfigProvider::class,
    TwigConfigProvider::class,
    MezzioConfigProvider::class,

    // App module(s) config
    CommonConfigProvider::class,
    ActorConfigProvider::class,
    ViewerConfigProvider::class,

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
