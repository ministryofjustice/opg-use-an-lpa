<?php

declare(strict_types=1);

namespace Common\Service\Session\KeyManager;

/**
 * Abstracted APCu functions out to aid with testing.
 *
 * Class KeyCache
 * @package Common\Service\Session\KeyManager
 */
class KeyCache
{
    /**
     * Retrieve a value from APCu
     *
     * @param string $key
     * @return mixed
     * @codeCoverageIgnore
     */
    public function get(string $key)
    {
        return apcu_fetch($key);
    }

    /**
     * Store a value in APCu
     *
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return array|bool
     * @codeCoverageIgnore
     */
    public function store(string $key, $value, int $ttl = 0)
    {
        return apcu_store($key, $value, $ttl);
    }
}
