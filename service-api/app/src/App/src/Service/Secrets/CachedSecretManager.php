<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use App\Service\Secrets\SecretManagerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Decorator of SecretManagerInterface classes that caches
 */
class CachedSecretManager implements SecretManagerInterface
{
    public function __construct(
        private CacheInterface $cache,
        private SecretManagerInterface $secretManager,
        private int $ttl = 3600,
    ) {
    }

    public function getSecretName(): string
    {
        return $this->secretManager->getSecretName();
    }

    public function getSecret(): string
    {
        $cacheKey = substr(sha1(self::class . $this->secretManager->getSecretName()), 0, 65);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $secret = $this->secretManager->getSecret();
        $this->cache->set($cacheKey, $secret, $this->ttl);

        return $secret;
    }
}
