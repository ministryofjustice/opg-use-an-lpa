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
        $this->config = $config;
    }

    public function getCookieName() : string
    {
        return 'session';
    }

    public function getCookiePath() : string
    {
        return '/';
    }

    public function getCacheLimiter() : string
    {
        return 'nocache';
    }

    public function getSessionExpired() : int
    {
        return 60 * 60 * 1; // 1 hour
    }

    public function getLastModified() : ?int
    {
        return null;
    }

    public function getPersistent() : bool
    {
        return false;
    }

    public function getCookieDomain() : ?string
    {
        return null;
    }

    public function getCookieSecure() : bool
    {
        return false;
    }

    public function getCookieHttpOnly() : bool
    {
        return false;
    }
}
