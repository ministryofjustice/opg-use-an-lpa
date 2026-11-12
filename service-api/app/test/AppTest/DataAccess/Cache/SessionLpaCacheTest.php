<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Cache;

use App\DataAccess\Cache\SessionLpaCache;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\Lpa;
use App\Service\Cache\CacheFactory;
use App\Service\Lpa\LpaDataFormatter;
use App\Service\Lpa\SiriusLpa;
use DateTimeImmutable;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use UnexpectedValueException;

#[CoversClass(SessionLpaCache::class)]
final class SessionLpaCacheTest extends TestCase
{
    private CacheFactory&MockObject $cacheFactory;
    private LoggerInterface&MockObject $logger;
    private ArrayAdapter $pool;
    private TagAwareAdapterInterface $cache;
    private SessionLpaCache $sut;

    public function setUp(): void
    {
        $this->pool         = new ArrayAdapter(3600, true);
        $this->cache        = new TagAwareAdapter($this->pool, new ArrayAdapter());
        $this->cacheFactory = $this->createMock(CacheFactory::class);
        $this->cacheFactory->method('taggable')->with('lpa-in-session')->willReturn($this->cache);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sut    = new SessionLpaCache($this->cacheFactory, $this->logger);
    }

    public static function lpaFormats(): array
    {
        return [
            'combined Sirius' => [
                'test_lpa.json',
                false,
            ],
            'LPA store'       => [
                '4000.json',
                false,
            ],
            'legacy Sirius'   => [
                'test_lpa.json',
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('lpaFormats')]
    public function it_should_cache_serialisable_lpas_across_requests(string $fixture, bool $legacy): void
    {
        $lpa = $this->lpa($fixture, $legacy);
        $uid = $lpa->getData()->getUid();

        $lookup = $this->createMock(LpasInterface::class);
        $lookup->expects($this->once())
            ->method('lookup')
            ->with([$uid])
            ->willReturn([$uid => $lpa]);

        $first = $this->sut->lookup([$uid], 'session-one', $lookup->lookup(...));

        $secondRequest = new SessionLpaCache($this->cacheFactory, $this->logger);
        $second        = $secondRequest->lookup([$uid], 'session-one', $lookup->lookup(...));

        self::assertSame($lpa, $first[$uid]);
        self::assertSame($lpa->getData()::class, $second[$uid]->getData()::class);
        self::assertSame(
            json_encode($first[$uid]->getData(), JSON_THROW_ON_ERROR),
            json_encode($second[$uid]->getData(), JSON_THROW_ON_ERROR),
        );
        self::assertEquals($lpa->getLookupTime(), $second[$uid]->getLookupTime());
        self::assertCount(1, array_filter($this->pool->getValues()));
    }

    #[Test]
    public function it_should_isolate_lpas_by_session(): void
    {
        $lpa = $this->lpa();
        $uid = $lpa->getData()->getUid();

        $lookup = $this->createMock(LpasInterface::class);
        $lookup->expects($this->exactly(2))
            ->method('lookup')
            ->with([$uid])
            ->willReturn([$lpa]);

        $this->sut->lookup([$uid], 'session-one', $lookup->lookup(...));
        $this->sut->lookup([$uid], 'session-two', $lookup->lookup(...));
        $this->sut->lookup([$uid], 'session-one', $lookup->lookup(...));

        self::assertCount(2, array_filter($this->pool->getValues()));

        foreach (array_keys($this->pool->getValues()) as $key) {
            self::assertStringNotContainsString('session-', $key);
        }
    }

    #[Test]
    public function it_should_cache_individual_lpas_and_only_fetch_misses_in_a_batch(): void
    {
        $one    = $this->lpa();
        $two    = $this->lpa('4000.json');
        $uidOne = $one->getData()->getUid();
        $uidTwo = $two->getData()->getUid();
        $calls  = [];
        $lookup = static function (array $uids) use (&$calls, $uidOne, $uidTwo, $one, $two): array {
            $calls[] = $uids;

            return array_intersect_key([$uidOne => $one, $uidTwo => $two], array_flip($uids));
        };

        $this->sut->lookup([$uidOne, $uidOne], 'session', $lookup);
        $result = $this->sut->lookup([$uidOne, $uidTwo, 'missing'], 'session', $lookup);
        $this->sut->lookup([$uidOne, $uidTwo, 'missing'], 'session', $lookup);
        $subset = $this->sut->lookup([$uidTwo], 'session', $lookup);

        self::assertSame([[$uidOne], [$uidTwo, 'missing'], ['missing']], $calls);
        self::assertEquals([$uidOne => $one, $uidTwo => $two], $result);
        self::assertEquals([$uidTwo => $two], $subset);
        self::assertCount(2, array_filter($this->pool->getValues()));
    }

    public static function absentSessions(): array
    {
        return [
            [null],
            [''],
        ];
    }

    #[Test]
    #[DataProvider('absentSessions')]
    public function it_should_bypass_caching_without_a_session(?string $sessionId): void
    {
        $lpa = $this->lpa();
        $uid = $lpa->getData()->getUid();

        $this->cacheFactory->expects($this->never())->method('taggable');

        $lookup = $this->createMock(LpasInterface::class);
        $lookup->expects($this->exactly(2))->method('lookup')->with([$uid])->willReturn([$lpa]);

        self::assertSame([$lpa], $this->sut->lookup([$uid], $sessionId, $lookup->lookup(...)));
        self::assertSame([$lpa], $this->sut->lookup([$uid], $sessionId, $lookup->lookup(...)));
    }

    #[Test]
    public function it_should_not_lookup_an_empty_collection(): void
    {
        $this->cacheFactory->expects($this->never())->method('taggable');

        $lookup = $this->createMock(LpasInterface::class);
        $lookup->expects($this->never())->method('lookup');

        self::assertSame([], $this->sut->lookup([], 'session', $lookup->lookup(...)));
    }

    #[Test]
    public function it_should_refetch_an_expired_lpa(): void
    {
        $lpa    = $this->lpa();
        $uid    = $lpa->getData()->getUid();
        $lookup = $this->createMock(LpasInterface::class);
        $lookup->expects($this->exactly(2))->method('lookup')->willReturn([$lpa]);

        $this->sut->lookup([$uid], 'session', $lookup->lookup(...));

        $key  = array_key_first($this->pool->getValues());
        $item = $this->pool->getItem($key);
        $this->pool->save($item->expiresAfter(-1));

        self::assertSame([$uid => $lpa], $this->sut->lookup([$uid], 'session', $lookup->lookup(...)));
    }

    #[Test]
    public function it_should_log_failed_cache_writes_and_return_the_lpa(): void
    {
        $lpa = $this->lpa();
        $uid = $lpa->getData()->getUid();

        $cache = $this->createMock(TagAwareAdapterInterface::class);
        $cache->method('getItem')->willReturn($this->cache->getItem('write-failure'));
        $cache->method('save')->willReturn(false);

        $factory = $this->createStub(CacheFactory::class);
        $factory->method('taggable')->willReturn($cache);

        $this->logger->expects($this->once())->method('warning')->with(
            'Unable to cache LPA {lpaUid} in session',
            ['lpaUid' => $uid],
        );

        $sut = new SessionLpaCache($factory, $this->logger);

        self::assertSame([$uid => $lpa], $sut->lookup([$uid], 'session', static fn (): array => [$lpa]));
    }

    #[Test]
    public function it_should_reject_cached_responses_without_lpa_data(): void
    {
        $cache = $this->createStub(TagAwareAdapterInterface::class);
        $cache->method('getItem')->willReturn((new CacheItem())->set(new Lpa(null, new DateTimeImmutable())));

        $factory = $this->createStub(CacheFactory::class);
        $factory->method('taggable')->willReturn($cache);

        $sut = new SessionLpaCache($factory, $this->logger);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid cached LPA data');

        $sut->lookup(['uid'], 'session', static fn (): array => []);
    }

    #[Test]
    public function it_should_reject_upstream_responses_without_lpa_data(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing LPA data');

        $this->sut->lookup(['uid'], 'session', static fn (): array => [new Lpa(null, null)]);
    }

    #[Test]
    public function it_should_propagate_lookup_errors_without_caching_them(): void
    {
        try {
            $this->sut->lookup(['uid'], 'session', static fn () => throw new RuntimeException('Upstream error'));
            self::fail('Expected lookup error');
        } catch (RuntimeException $exception) {
            self::assertSame('Upstream error', $exception->getMessage());
        }

        self::assertSame([], array_filter($this->pool->getValues()));
    }

    public static function invalidations(): array
    {
        return [
            'session'        => [
                'invalidateSession',
                [
                    true,
                    true,
                    false,
                    false,
                ],
            ],
            'LPA'            => [
                'invalidateLpa',
                [
                    true,
                    false,
                    true,
                    false,
                ],
            ],
            'LPA in session' => [
                'invalidateLpaInSession',
                [
                    true,
                    false,
                    false,
                    false,
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidations')]
    public function it_should_invalidate_only_the_selected_entries(string $method, array $expectedMisses): void
    {
        $one    = $this->lpa();
        $two    = $this->lpa('4000.json');
        $uids   = [
            $one->getData()->getUid(),
            $two->getData()->getUid(),
        ];
        $lookup = static fn (): array => [
            $one,
            $two,
        ];

        $this->sut->lookup($uids, 'session-one', $lookup);
        $this->sut->lookup($uids, 'session-two', $lookup);

        match ($method) {
            'invalidateSession' => $this->sut->invalidateSession('session-one'),
            'invalidateLpa' => $this->sut->invalidateLpa($uids[0]),
            'invalidateLpaInSession' => $this->sut->invalidateLpaInSession('session-one', $uids[0]),
        };

        $index = 0;

        foreach (['session-one', 'session-two'] as $session) {
            foreach ($uids as $uid) {
                $key = 'tagged-lpa-' . hash('sha256', $session . "\0" . $uid);

                self::assertSame(!$expectedMisses[$index++], $this->cache->getItem($key)->isHit());
            }
        }

        $calls = [];

        $this->sut->lookup($uids, 'session-one', static function (array $missing) use (&$calls): array {
            $calls = $missing;

            return [];
        });

        self::assertSame(array_values(array_filter(
            $uids,
            static fn (string $uid): bool => $expectedMisses[array_search($uid, $uids, true)],
        )), $calls);
    }

    #[Test]
    #[DataProvider('invalidations')]
    public function it_should_surface_invalidation_failures(string $method): void
    {
        $cache = $this->createMock(TagAwareAdapterInterface::class);
        $cache->method('invalidateTags')->willReturn(false);
        $cache->method('deleteItem')->willReturn(false);

        $factory = $this->createStub(CacheFactory::class);
        $factory->method('taggable')->willReturn($cache);

        $sut = new SessionLpaCache($factory, $this->logger);

        $this->expectException(RuntimeException::class);

        match ($method) {
            'invalidateSession' => $sut->invalidateSession('session'),
            'invalidateLpa' => $sut->invalidateLpa('uid'),
            'invalidateLpaInSession' => $sut->invalidateLpaInSession('session', 'uid'),
        };
    }

    private function lpa(string $fixture = 'test_lpa.json', bool $legacy = false): Lpa
    {
        $data = json_decode(
            file_get_contents(__DIR__ . '/../../../fixtures/' . $fixture),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        // A closure in the logger ensures the legacy object itself cannot be serialised.
        $logger = new Logger('test', processors: [static fn ($record) => $record]);

        return new Lpa(
            $legacy ? new SiriusLpa($data, $logger) : (new LpaDataFormatter())->hydrateObject($data),
            new DateTimeImmutable('2026-11-09T12:00:00+00:00'),
        );
    }
}
