<?php

declare(strict_types=1);

namespace CommonTest\Service\Security\RateLimit;

use Common\Exception\RateLimitExceededException;
use Common\Service\Security\RateLimit\KeyedRateLimitService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(KeyedRateLimitService::class)]
class KeyedRateLimitServiceTest extends TestCase
{
    #[Test]
    public function it_will_limit_an_identity_when_required(): void
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
            time() - 40,
            time() - 50,
        ];

        [
            $cachePool,
            $cacheItem,
        ] = $this->createCacheItem(true, $records);
        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($records)
            ->willReturn($cacheItem);
        $cachePool
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $keyedRateLimitService = new KeyedRateLimitService(
            $cachePool,
            'test-limiter',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_will_check_if_a_request_is_limited(): void
    {
        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool
            ->expects($this->once())
            ->method('getItem')
            ->with($this->cacheKey())
            ->willReturn($cacheItem);

        $keyedRateLimitService = new KeyedRateLimitService(
            $cachePool,
            'test-limiter',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_will_move_a_cache_window_dropping_records(): void
    {
        $beforeWindow = [
            time() - 20,
            time() - 30,
            time() - 65, // outside 60 second window
        ];

        [
            $cachePool,
            $cacheItem,
        ] = $this->createCacheItem(true, $beforeWindow);
        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with([$beforeWindow[0], $beforeWindow[1]])
            ->willReturn($cacheItem);
        $cachePool
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $keyedRateLimitService = new KeyedRateLimitService(
            $cachePool,
            'test-limiter',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_will_record_an_identity_access(): void
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
        ];

        [
            $cachePool,
            $cacheItem,
        ] = $this->createCacheItem(true, $records);
        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($this->callback(static fn (mixed $accessRecords): bool => is_array($accessRecords)
                && count($accessRecords) === 4
                && array_slice($accessRecords, 0, 3) === $records
            && is_int($accessRecords[3])))
            ->willReturn($cacheItem);
        $cachePool
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $keyedRateLimitService = new KeyedRateLimitService(
            $cachePool,
            'test-limiter',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        );

        $keyedRateLimitService->limit('test-identity');
    }

    #[Test]
    public function it_will_throw_an_exception_when_recording_a_limit_that_exceeds(): void
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
            time() - 40,
        ];

        [
            $cachePool,
            $cacheItem,
        ] = $this->createCacheItem(true, $records, 'cache-namespace-name');
        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($this->callback(static fn (array $accessRecords): bool => count($accessRecords) === 5));
        $cachePool
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $keyedRateLimitService = new KeyedRateLimitService(
            $cachePool,
            'cache-namespace-name',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionMessage('cache-namespace-name rate limit exceeded for identity test-identity');
        $keyedRateLimitService->limit('test-identity');
    }

    /**
     * @param list<int> $records
     * @return array{CacheItemPoolInterface, CacheItemInterface}
     */
    private function createCacheItem(bool $isHit, array $records = [], string $limiterName = 'test-limiter'): array
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn($isHit);
        $cacheItem->method('get')->willReturn($records);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool
            ->method('getItem')
            ->with($this->cacheKey($limiterName))
            ->willReturn($cacheItem);

        return [
            $cachePool,
            $cacheItem,
        ];
    }

    private function cacheKey(string $limiterName = 'test-limiter'): string
    {
        return hash('sha256', $limiterName . ':test-identity:');
    }
}
