<?php

declare(strict_types=1);

namespace Service\SystemMessage;

use App\Service\SystemMessage\SystemMessage;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SystemMessageTest extends TestCase
{
    /**
 * @var SsmClient&MockObject
 */
    private SsmClient $ssmClient;
    private SystemMessage $systemMessage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ssmClient = $this->createMock(SsmClient::class);

        $this->systemMessage = new SystemMessage($this->ssmClient);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws IncompatibleReturnValueException
     */
    public function testReturnsSystemMessages(): void
    {
        $mockResponse = [
            'Parameters' => [
                ['Name' => '/system-message/use/en', 'Value' => 'English usage message'],
                ['Name' => '/system-message/use/cy', 'Value' => 'Welsh usage message'],
            ],
        ];
        $this->ssmClient->method('getParameters')
            ->willReturn($mockResponse);

        $systemMessages = $this->systemMessage->getSystemMessages();

        $expected = [
            '/system-message/use/en' => 'English usage message',
            '/system-message/use/cy' => 'Welsh usage message',
        ];
        $this->assertEquals($expected, $systemMessages);
    }
}
