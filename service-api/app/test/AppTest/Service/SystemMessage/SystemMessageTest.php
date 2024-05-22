<?php

declare(strict_types=1);

namespace AppTest\Service\SystemMessage;

use App\Service\SystemMessage\SystemMessage;
use Aws\Ssm\SsmClient;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception as PHPUnitException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SystemMessageTest extends TestCase
{
    /**
     * @var SsmClient&MockObject
     */
    use ProphecyTrait;

    private SsmClient $ssmClient;
    private SystemMessage $systemMessage;

    /**
     * @throws MockObjectException
     * @throws NoPreviousThrowableException
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ssmClient = $this->createMock(SsmClient::class);

        $this->systemMessage = new SystemMessage($this->ssmClient, '/system-message/');
    }

    /**
     * @throws PHPUnitException
     */
    #[Test]
    public function returnsSystemMessages(): void
    {
        $mockResponse = [
            'Parameters' => [
                ['Name' => '/system-message/use/en', 'Value' => 'English usage message'],
                ['Name' => '/system-message/use/cy', 'Value' => 'Welsh usage message'],
            ],
        ];

        $this->ssmClient->method('__call')
            ->with($this->identicalTo('getParameters'))
            ->willReturn($mockResponse);

        $systemMessages = $this->systemMessage->getSystemMessages();

        $expected = [
            'use/en' => 'English usage message',
            'use/cy' => 'Welsh usage message',
        ];

        $this->assertEquals($expected, $systemMessages);
    }

    /**
     * @throws PHPUnitException
     */
    #[Test]
    public function correctlyHandlesStoredWhitespace(): void
    {
        $mockResponse = [
            'Parameters' => [
                ['Name' => '/system-message/use/en', 'Value' => ' '],
                ['Name' => '/system-message/use/cy', 'Value' => ' '],
            ],
        ];

        $this->ssmClient->method('__call')
            ->with($this->identicalTo('getParameters'))
            ->willReturn($mockResponse);

        $systemMessages = $this->systemMessage->getSystemMessages();

        $expected = [
            'use/en' => '',
            'use/cy' => '',
        ];

        $this->assertEquals($expected, $systemMessages);
    }
}
