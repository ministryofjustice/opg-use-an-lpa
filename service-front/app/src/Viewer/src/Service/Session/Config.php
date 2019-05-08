<?php

declare(strict_types=1);

namespace Viewer\Service\Session;

use RuntimeException;

class Config
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        if (!array_key_exists('session', $config)) {
            throw new RuntimeException("Session configuration missing");
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

        $this->config = $config;
    }

    public function getCookieName() : string
    {
        return $this->config['session']['cookie_name'];
    }

    public function getCookiePath() : string
    {
        return $this->config['session']['cookie_path'];
    }

    public function getCacheLimiter() : string
    {
        return $this->config['session']['cache_limiter'];
    }

    public function getSessionExpired() : int
    {
        return $this->config['session']['expires'];
    }

    public function getLastModified() : ?int
    {
        return $this->config['session']['last_modified'];
    }

    public function getPersistent() : bool
    {
        return $this->config['session']['persistent'];
    }

    public function getCookieDomain() : ?string
    {
        return $this->config['session']['cookie_domain'];
    }

    public function getCookieSecure() : bool
    {
        return $this->config['session']['cookie_secure'];
    }

    public function getCookieHttpOnly() : bool
    {
        return $this->config['session']['cookie_http_only'];
    }
}
