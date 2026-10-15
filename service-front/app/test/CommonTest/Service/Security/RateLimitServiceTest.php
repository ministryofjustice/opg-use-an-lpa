<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\RateLimitService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(RateLimitService::class)]
class RateLimitServiceTest extends TestCase
{
    #[Test]
    public function its_name_comes_from_the_cache_definition(): void
    {
        $rateLimitService = new class (
            new ArrayAdapter(),
            'cache-namespace-name',
            60,
            4,
            $this->createStub(LoggerInterface::class),
        ) extends RateLimitService {
            public function isLimited(string $identity): bool
            {
                return false;
            }

            public function limit(string $identity): void
            {
            }
        };

        $name = $rateLimitService->getName();

        $this->assertSame('cache-namespace-name', $name);
    }
}
