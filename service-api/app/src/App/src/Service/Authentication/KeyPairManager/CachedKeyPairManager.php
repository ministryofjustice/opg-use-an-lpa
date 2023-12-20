<?php

declare(strict_types=1);

namespace App\Service\Authentication\KeyPairManager;

use Psr\SimpleCache\CacheInterface;

/**
 * Decorator of KeyPairMangagerInterface classes that caches
 */
class CachedKeyPairManager implements KeyPairManagerInterface
{
    public function __construct(
        private CacheInterface $cache,
        private KeyPairManagerInterface $keyPairManager,
        private int $ttl = 3600,
    ) {
    }

    public function getKeyPair(): KeyPair
    {
        $cacheKey = substr(sha1(self::class . $this->keyPairManager::PUBLIC_KEY), 0, 65);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $keyPair = $this->keyPairManager->getKeyPair();
        $this->cache->set($cacheKey, $keyPair, $this->ttl);

        return $keyPair;
    }

    public function getAlgorithm(): string
    {
        return $this->keyPairManager->getAlgorithm();
    }
}
