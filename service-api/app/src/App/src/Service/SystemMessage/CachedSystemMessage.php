<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

use Psr\SimpleCache\CacheInterface;

class CachedSystemMessage implements SystemMessageService
{
    public const CACHE_KEY = 'system-messages';

    public function __construct(
        private SystemMessageService $systemMessageService,
        private CacheInterface $cache,
    ) {
    }

    public function getSystemMessages(): array
    {
        $cachedMessages = $this->cache->get(self::CACHE_KEY);
        if ($cachedMessages !== null) {
            return $cachedMessages;
        }

        $systemMessages = $this->systemMessageService->getSystemMessages();
        $this->cache->set(self::CACHE_KEY, $systemMessages);

        return $systemMessages;
    }
}
