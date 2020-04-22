<?php

declare(strict_types=1);

namespace CommonTest\Service\Security\RateLimit;

use Common\Exception\RateLimitExceededException;
use Common\Service\Security\RateLimit\KeyedRateLimitService;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class KeyedRateLimitServiceTest extends TestCase
{
    /** @test */
    public function it_will_limit_an_identity_when_required()
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
            time() - 40,
            time() - 50
        ];

        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getItem('test-identity:')
            ->shouldBeCalled()
            ->willReturn($records);
        $storageProphecy
            ->setItem('test-identity:', $records)
            ->shouldBeCalled();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $keyedRateLimitService = new KeyedRateLimitService(
            $storageProphecy->reveal(),
            60,
            4,
            $loggerProphecy->reveal()
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_will_check_if_a_request_is_limited()
    {
        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getItem('test-identity:')
            ->shouldBeCalled()
            ->willReturn(null);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $keyedRateLimitService = new KeyedRateLimitService(
            $storageProphecy->reveal(),
            60,
            4,
            $loggerProphecy->reveal()
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_will_move_a_cache_window_dropping_records()
    {
        $beforeWindow = [
            time() - 20,
            time() - 30,
            time() - 65 // outside 60 second window
        ];

        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getItem('test-identity:')
            ->shouldBeCalled()
            ->willReturn($beforeWindow);
        $storageProphecy
            ->setItem(
                'test-identity:',
                [
                    $beforeWindow[0],
                    $beforeWindow[1]
                ]
            )
            ->shouldBeCalled();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $keyedRateLimitService = new KeyedRateLimitService(
            $storageProphecy->reveal(),
            60,
            4,
            $loggerProphecy->reveal()
        );

        $result = $keyedRateLimitService->isLimited('test-identity');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_will_record_an_identity_access()
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
        ];

        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getItem('test-identity:')
            ->shouldBeCalled()
            ->willReturn($records);
        $storageProphecy
            ->setItem(
                'test-identity:',
                array_merge(
                    $records,
                    [time()]
                )
            )
            ->shouldBeCalled();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $keyedRateLimitService = new KeyedRateLimitService(
            $storageProphecy->reveal(),
            60,
            4,
            $loggerProphecy->reveal()
        );

        $keyedRateLimitService->limit('test-identity');
    }

    /** @test */
    public function it_will_throw_an_exception_when_recording_a_limit_that_exceeds()
    {
        $records = [
            time() - 10,
            time() - 20,
            time() - 30,
            time() - 40,
        ];

        $adapterProphecy = $this->prophesize(AdapterOptions::class);
        $adapterProphecy->getNamespace()->willReturn('cache-namespace-name');

        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getOptions()
            ->willReturn($adapterProphecy->reveal());
        $storageProphecy
            ->getItem('test-identity:')
            ->shouldBeCalled()
            ->willReturn($records);
        $storageProphecy
            ->setItem(
                'test-identity:',
                array_merge(
                    $records,
                    [time()]
                )
            )
            ->shouldBeCalled();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $keyedRateLimitService = new KeyedRateLimitService(
            $storageProphecy->reveal(),
            60,
            4,
            $loggerProphecy->reveal()
        );

        $this->expectException(RateLimitExceededException::class);
        $keyedRateLimitService->limit('test-identity');
    }
}
