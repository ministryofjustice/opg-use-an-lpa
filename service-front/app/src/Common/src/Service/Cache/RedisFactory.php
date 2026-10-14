<?php

declare(strict_types=1);

namespace Common\Service\Cache;

use Redis;

/**
 * @psalm-api
 */
final class RedisFactory
{
    public function __invoke(): Redis
    {
        return new Redis();
    }
}
