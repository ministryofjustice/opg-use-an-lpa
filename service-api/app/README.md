# Expressive Skeleton and Installer

[![Build Status](https://secure.travis-ci.org/mezzio/mezzio-skeleton.svg?branch=master)](https://secure.travis-ci.org/mezzio/mezzio-skeleton)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-skeleton/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-skeleton?branch=master)

*Begin developing PSR-15 middleware applications in seconds!*

[Zend Expressive](https://github.com/mezzio/mezzio) builds on
[zend-stratigility](https://github.com/laminas/laminas-stratigility) to
provide a minimalist PSR-15 middleware framework for PHP with routing, DI
container, optional templating, and optional error handling capabilities.

This installer will setup a skeleton application based on zend-expressive by
choosing optional packages based on user input as demonstrated in the following
screenshot:

![screenshot-installer](https://cloud.githubusercontent.com/assets/459648/10410494/16bdc674-6f6d-11e5-8190-3c1466e93361.png)

The user selected packages are saved into `composer.json` so that everyone else
working on the project have the same packages installed. Configuration files and
templates are prepared for first use. The installer command is removed from
`composer.json` after setup succeeded, and all installer related files are
removed.

## Getting Started

Start your new Expressive project with composer:

```bash
$ composer create-project mezzio/mezzio-skeleton <project-path>
```

After choosing and installing the packages you want, go to the
`<project-path>` and start PHP's built-in web server to verify installation:

```bash
$ composer run --timeout=0 serve
```

You can then browse to http://localhost:8080.

> ### Linux users
>
> On PHP versions prior to 7.1.14 and 7.2.2, this command might not work as
> expected due to a bug in PHP that only affects linux environments. In such
> scenarios, you will need to start the [built-in web
> server](http://php.net/manual/en/features.commandline.webserver.php) yourself,
> using the following command:
>
> ```bash
> $ php -S 0.0.0.0:8080 -t public/ public/index.php
> ```

> ### Setting a timeout
>
> Composer commands time out after 300 seconds (5 minutes). On Linux-based
> systems, the `php -S` command that `composer serve` spawns continues running
> as a background process, but on other systems halts when the timeout occurs.
>
> As such, we recommend running the `serve` script using a timeout. This can
> be done by using `composer run` to execute the `serve` script, with a
> `--timeout` option. When set to `0`, as in the previous example, no timeout
> will be used, and it will run until you cancel the process (usually via
> `Ctrl-C`). Alternately, you can specify a finite timeout; as an example,
> the following will extend the timeout to a full day:
>
> ```bash
> $ composer run --timeout=86400 serve
> ```

## Troubleshooting

If the installer fails during the ``composer create-project`` phase, please go
through the following list before opening a new issue. Most issues we have seen
so far can be solved by `self-update` and `clear-cache`.

1. Be sure to work with the latest version of composer by running `composer self-update`.
2. Try clearing Composer's cache by running `composer clear-cache`.

If neither of the above help, you might face more serious issues:

- Info about the [zlib_decode error](https://github.com/composer/composer/issues/4121).
- Info and solutions for [composer degraded mode](https://getcomposer.org/doc/articles/troubleshooting.md#degraded-mode).

## Application Development Mode Tool

This skeleton comes with [laminas-development-mode](https://github.com/laminas/laminas-development-mode).
It provides a composer script to allow you to enable and disable development mode.

### To enable development mode

**Note:** Do NOT run development mode on your production server!

```bash
$ composer development-enable
```

**Note:** Enabling development mode will also clear your configuration cache, to
allow safely updating dependencies and ensuring any new configuration is picked
up by your application.

### To disable development mode

```bash
$ composer development-disable
```

### Development mode status

```bash
$ composer development-status
```

## Configuration caching

### Opt-in Symfony application cache

`App\Service\Cache\Symfony\ConfigProvider` supplies standalone, lazy cache
factories compatible with Laminas ServiceManager (and PSR-11 containers using
the same factory configuration). It is deliberately **not registered** in
`config/config.php`; existing Laminas cache configuration and consumers are unchanged.

The bootstrap follows [FrameworkBundle's cache services](https://github.com/symfony/symfony/blob/6.4/src/Symfony/Bundle/FrameworkBundle/Resources/config/cache.php):

| Service | Default |
| --- | --- |
| `cache.request` | Request-local array pool, shared by the PSR-6 and Symfony Cache contract aliases |
| `cache.request.taggable` | Tag-aware wrapper around `cache.request`, aliased to the tag-aware contract |
| `cache.app` | Long-lived APCu application pool, explicitly selected by service name |
| `cache.app.taggable` | Tag-aware wrapper around the long-lived application pool |

Symfony Cache 6.4 LTS is used because the existing Laminas fork requires
`psr/cache` v2. Symfony Cache 7.4 requires v3; upgrading that belongs to the
eventual migration. No PSR-16 alias is registered.

For an isolated bootstrap, without changing the application:

```php
$config = (new \App\Service\Cache\Symfony\ConfigProvider())();
$container = new \Laminas\ServiceManager\ServiceManager(
    $config['dependencies'] + ['services' => ['config' => $config]]
);
$cache = $container->get(\Symfony\Contracts\Cache\CacheInterface::class); // cache.request
$appCache = $container->get('cache.app');
```

For later opt-in use, add the provider to the config aggregator **before** the
autoloaded configuration, then override its separate `symfony_cache` section:

```php
return [
    'symfony_cache' => [
        'namespace' => 'use-an-lpa-api-production',
        'version' => 'release-id',
        'pools' => [
            'cache.app' => [
                'adapter' => 'apcu',
                'default_lifetime' => 300,
            ],
            'cache.example' => [
                'adapter' => 'array',
                'default_lifetime' => 60,
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'cache.example' => \App\Service\Cache\Symfony\PoolFactory::class,
        ],
    ],
];
```

Supported adapters are `array` (the default request cache, serialized and
instance-local) and `apcu` (the application cache).
Additional pools require both a pool configuration and a factory
registration. Lifetimes are non-negative integer seconds; zero means no default
expiry. A stable namespace seed and the requested service name determine each
APCu pool's isolated storage namespace. Set a distinct seed per application/environment.
`version` is a non-empty string (default `1`) which invalidates APCu data when
changed. Array pools keep their data in their own instances; namespace/version
settings do not affect their storage. APCu selection fails if APCu is unavailable
(enable `apc.enable_cli` for CLI usage); there is no automatic fallback.
No filesystem directory or custom marshaller configuration is needed.

Both default pools have a lifetime of zero. The request pool lasts only as long
as its container-owned instance; in the usual request-scoped PHP lifecycle its
data disappears after the request. Long-running workers reusing a container must
reset the request pool and its tag-aware wrapper between requests (no lifecycle
hook is installed here). The application pool persists across requests on the
same APCu instance, without a default TTL, but can still be evicted, cleared,
invalidated by version changes, or lost when APCu restarts. It is not durable or
shared across application hosts. Items can specify their own expiry.

If `Psr\Log\LoggerInterface` is registered, factories inject it into the adapters.
Invalid configuration and invalid logger services raise exceptions.
This is component bootstrap only: FrameworkBundle's compiler passes, framework
pools, warmer/clearer commands, Messenger integration and lifecycle reset hooks
are not installed. Pools expose their own `clear()`/`reset()` operations.

### Container configuration cache

By default, the skeleton will create a configuration cache in
`data/config-cache.php`. When in development mode, the configuration cache is
disabled, and switching in and out of development mode will remove the
configuration cache.

You may need to clear the configuration cache in production when deploying if
you deploy to the same directory. You may do so using the following:

```bash
$ composer clear-config-cache
```

You may also change the location of the configuration cache itself by editing
the `config/config.php` file and changing the `config_cache_path` entry of the
local `$cacheConfig` variable.

## Skeleton Development

This section applies only if you cloned this repo with `git clone`, not when you
installed expressive with `composer create-project ...`.

If you want to run tests against the installer, you need to clone this repo and
setup all dependencies with composer.  Make sure you **prevent composer running
scripts** with `--no-scripts`, otherwise it will remove the installer and all
tests.

```bash
$ composer update --no-scripts
$ composer test
```

Please note that the installer tests remove installed config files and templates
before and after running the tests.

Before contributing read [the contributing guide](docs/CONTRIBUTING.md).
