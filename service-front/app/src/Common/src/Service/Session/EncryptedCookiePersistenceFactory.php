<?php

declare(strict_types=1);

namespace Common\Service\Session;

use Common\Service\Session\Encryption\EncryptInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class EncryptedCookiePersistenceFactory
{
    public function __invoke(ContainerInterface $container): EncryptedCookiePersistence
    {
        $config = $container->get('config');

        if (!isset($config['session'])) {
            throw new RuntimeException('Session configuration missing');
        }

        if (!array_key_exists('cookie_name', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_name');
        }

        if (!array_key_exists('cookie_path', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_path');
        }

        if (!array_key_exists('cache_limiter', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cache_limiter');
        }

        if (!array_key_exists('expires', $config['session'])) {
            throw new RuntimeException('Missing session configuration: expires');
        }

        if (!array_key_exists('last_modified', $config['session'])) {
            throw new RuntimeException('Missing session configuration: last_modified');
        }

        if (!array_key_exists('cookie_ttl', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_ttl');
        }

        if (!array_key_exists('cookie_domain', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_domain');
        }

        if (!array_key_exists('cookie_secure', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_secure');
        }

        if (!array_key_exists('cookie_http_only', $config['session'])) {
            throw new RuntimeException('Missing session configuration: cookie_http_only');
        }

        return new EncryptedCookiePersistence(
            encrypter:      $container->get(EncryptInterface::class),
            cookieName:     $config['session']['cookie_name'],
            cookiePath:     $config['session']['cookie_path'],
            cacheLimiter:   $config['session']['cache_limiter'],
            cacheExpire:    $config['session']['expires'],
            lastModified:   $config['session']['last_modified'],
            cookieLifetime: $config['session']['cookie_ttl'],
            cookieDomain:   $config['session']['cookie_domain'],
            cookieSecure:   $config['session']['cookie_secure'],
            cookieHttpOnly: $config['session']['cookie_http_only']
        );
    }
}
