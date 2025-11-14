<?php

declare(strict_types=1);

namespace AppTest\Service\SystemMessage;

use App\Service\SystemMessage\CachedSystemMessage;
use App\Service\SystemMessage\SystemMessageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

class CachedSystemMessageTest extends TestCase
{
    use ProphecyTrait;

    private SystemMessageService $systemMessage;

    private CacheInterface $cache;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemMessage = $this->createMock(SystemMessageService::class);
        $this->cache         = $this->createMock(CacheInterface::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function returnsCachedSystemMessages(): void
    {
        $this->cache->method('get')
            ->with('system-messages')
            ->willReturn(['use/en' => 'Use English', 'use/cy' => 'Use Welsh']);

        $cachedSystemMessageService = new CachedSystemMessage($this->systemMessage, $this->cache);

        $systemMessages = $cachedSystemMessageService->getSystemMessages();

        $expected = [
            'use/en' => 'Use English',
            'use/cy' => 'Use Welsh',
        ];

        $this->assertSame($expected, $systemMessages);
    }

    #[Test]
    public function returnsSystemMessages_notCached(): void
    {
        $cachedSystemMessageService = new CachedSystemMessage($this->systemMessage, $this->cache);

        $this->systemMessage->method('getSystemMessages')->willReturn([
            'use/en' => 'Uncached Use English',
            'use/cy' => 'Uncached Use Welsh',
        ]);

        $systemMessages = $cachedSystemMessageService->getSystemMessages();

        $expected = [
            'use/en' => 'Uncached Use English',
            'use/cy' => 'Uncached Use Welsh',
        ];

        $this->assertSame($expected, $systemMessages);
    }
}
