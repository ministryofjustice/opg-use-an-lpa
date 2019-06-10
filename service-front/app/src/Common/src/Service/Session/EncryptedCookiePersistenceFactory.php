<?php

declare(strict_types=1);

namespace Common\Service\Session;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Common\Service\Session\KeyManager\KeyManagerInterface;

/**
 * Class EncryptedCookiePersistenceFactory
 * @package Common\Service\Session
 */
class EncryptedCookiePersistenceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['session'])) {
            throw new RuntimeException('Session configuration missing');
        }

        if (!array_key_exists('expires', $config['session'])) {
            throw new RuntimeException("Missing session configuration: expires");
        }

        if (!array_key_exists('cookie_name', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cookie_name");
        }

        if (!array_key_exists('cookie_domain', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cookie_domain");
        }

        if (!array_key_exists('cookie_path', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cookie_path");
        }

        if (!array_key_exists('cookie_secure', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cookie_secure");
        }

        if (!array_key_exists('cookie_http_only', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cookie_http_only");
        }

        if (!array_key_exists('cache_limiter', $config['session'])) {
            throw new RuntimeException("Missing session configuration: cache_limiter");
        }

        if (!array_key_exists('last_modified', $config['session'])) {
            throw new RuntimeException("Missing session configuration: last_modified");
        }

        if (!array_key_exists('persistent', $config['session'])) {
            throw new RuntimeException("Missing session configuration: persistent");
        }

        return new EncryptedCookiePersistence(
            $container->get(KeyManagerInterface::class),
            $config['session']['cookie_name'],
            $config['session']['cookie_path'],
            $config['session']['cache_limiter'],
            $config['session']['expires'],
            $config['session']['last_modified'],
            $config['session']['persistent'],
            $config['session']['cookie_domain'],
            $config['session']['cookie_secure'],
            $config['session']['cookie_http_only']
        );
    }
}
