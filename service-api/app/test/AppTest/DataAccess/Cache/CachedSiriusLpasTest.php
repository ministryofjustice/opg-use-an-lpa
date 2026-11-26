<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Cache;

use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Cache\CachedSiriusLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

#[CoversClass(CachedSiriusLpas::class)]
class CachedSiriusLpasTest extends TestCase
{
    private SiriusLpas&MockObject $siriusLpas;

    public function setUp(): void
    {
        $this->siriusLpas = $this->createMock(SiriusLpas::class);
    }

    #[Test]
    public function it_should_cache_an_lpa_fetched_by_get(): void
    {
        $lpa = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

        $sut = $this->createCachedSiriusLpas();

        $this->assertSame($lpa, $sut->get('700000000001'));
        $this->assertSame($lpa, $sut->get('700000000001'));
    }

    #[Test]
    public function it_should_share_cached_lpas_between_instances_using_the_request_cache(): void
    {
        $lpa = $this->createStub(LpaInterface::class);
        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

        $cache  = $this->createCache();
        $first  = new CachedSiriusLpas(
            $this->siriusLpas,
            $cache,
            $this->createStub(LoggerInterface::class),
        );
        $second = new CachedSiriusLpas(
            $this->siriusLpas,
            $cache,
            $this->createStub(LoggerInterface::class),
        );

        self::assertSame($lpa, $first->get('700000000001'));
        self::assertSame($lpa, $second->get('700000000001'));
    }

    #[Test]
    public function it_should_not_cache_an_lpa_that_was_not_found(): void
    {
        $this->siriusLpas
            ->expects($this->exactly(2))
            ->method('get')
            ->with('700000000001')
            ->willReturn(null);

        $sut = $this->createCachedSiriusLpas();

        $this->assertNull($sut->get('700000000001'));
        $this->assertNull($sut->get('700000000001'));
    }

    #[Test]
    public function it_should_only_lookup_lpas_that_are_not_cached(): void
    {
        $lpaOne   = $this->createStub(LpaInterface::class);
        $lpaTwo   = $this->createStub(LpaInterface::class);
        $lpaThree = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpaOne);

        $this->siriusLpas
            ->expects($this->exactly(2))
            ->method('lookupAsync')
            ->willReturnCallback(
                fn (array $uids, ?callable $onLpa): PromiseInterface => match ($uids) {
                    [
                        '700000000002',
                        '700000000003',
                        '700000000004',
                    ] => $this->fulfilLookup(
                        [
                            '700000000002' => $lpaTwo,
                            '700000000003' => $lpaThree,
                        ],
                        $onLpa,
                    ),
                    ['700000000004'] => $this->fulfilLookup([], $onLpa),
                }
            );

        $sut = $this->createCachedSiriusLpas();

        $sut->get('700000000001');

        $expected = [
            '700000000001' => $lpaOne,
            '700000000002' => $lpaTwo,
            '700000000003' => $lpaThree,
        ];

        $uids = [
            '700000000001',
            '700000000002',
            '700000000003',
            '700000000004',
        ];

        $this->assertSame($expected, $sut->lookup($uids));
        $this->assertSame($expected, $sut->lookup($uids));
        $this->assertSame($lpaTwo, $sut->get('700000000002'));
    }

    #[Test]
    public function it_should_not_call_sirius_when_all_lpas_are_cached(): void
    {
        $lpa = $this->createStub(LpaInterface::class);

        $this->siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

        $this->siriusLpas->expects($this->never())->method('lookupAsync');

        $sut = $this->createCachedSiriusLpas();

        $sut->get('700000000001');

        $this->assertSame(['700000000001' => $lpa], $sut->lookup(['700000000001']));
    }

    #[Test]
    public function it_should_log_cache_activity_at_debug_level(): void
    {
        $this->siriusLpas->method('get')->willReturn($this->createStub(LpaInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('debug');

        $sut = new CachedSiriusLpas($this->siriusLpas, $this->createCache(), $logger);
        $sut->get('700000000001');
    }

    #[Test]
    public function it_should_invoke_the_callback_for_cached_and_fetched_lpas(): void
    {
        $lpaOne = $this->createStub(LpaInterface::class);
        $lpaTwo = $this->createStub(LpaInterface::class);

        $this->siriusLpas->method('get')->willReturn($lpaOne);
        $this->siriusLpas
            ->expects($this->once())
            ->method('lookupAsync')
            ->with(['700000000002'])
            ->willReturnCallback(
                fn (array $uids, ?callable $onLpa): PromiseInterface => $this->fulfilLookup(
                    ['700000000002' => $lpaTwo],
                    $onLpa,
                ),
            );

        $sut = $this->createCachedSiriusLpas();
        $sut->get('700000000001');

        $seen   = [];
        $result = $sut
            ->lookupAsync(
                [
                    '700000000001',
                    '700000000002',
                ],
                function (LpaInterface $lpa, string $uid) use (&$seen): PromiseInterface {
                    return Create::promiseFor(null)->then(function () use ($lpa, $uid, &$seen): void {
                        $seen[$uid] = $lpa;
                    });
                },
            )
            ->wait();

        $expected = [
            '700000000001' => $lpaOne,
            '700000000002' => $lpaTwo,
        ];

        $this->assertSame($expected, $result);
        $this->assertSame($expected, $seen);
    }

    /**
     * Fulfils a lookup in the same way as SiriusLpas, invoking the callback for each LPA.
     *
     * @param array<string, LpaInterface> $lpas
     */
    private function fulfilLookup(array $lpas, ?callable $onLpa): PromiseInterface
    {
        $promises = [];
        foreach ($lpas as $uid => $lpa) {
            $promises[] = $onLpa !== null ? $onLpa($lpa, (string) $uid) : null;
        }

        return Utils::all($promises)->then(fn (): array => $lpas);
    }

    private function createCachedSiriusLpas(): CachedSiriusLpas
    {
        return new CachedSiriusLpas(
            $this->siriusLpas,
            $this->createCache(),
            $this->createStub(LoggerInterface::class),
        );
    }

    private function createCache(): CacheInterface
    {
        return new Psr16Cache(new ArrayAdapter(0, false));
    }
}
