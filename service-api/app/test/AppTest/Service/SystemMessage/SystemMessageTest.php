<?php

declare(strict_types=1);

namespace Service\SystemMessage;

use App\Service\SystemMessage\SystemMessage;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
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

        // TODO the following line does not work. It needs something else mocking. we tried to do this elsewhere but ran into other problems
        // this test can be deleted once the 2cnd version using prophecy below, is working , as it will replace this
        $this->ssmClient->method('getParameters')
            ->willReturn($mockResponse);

        $systemMessages = $this->systemMessage->getSystemMessages();

        $expected = [
            '/system-message/use/en' => 'English usage message',
            '/system-message/use/cy' => 'Welsh usage message',
        ];
        $this->assertEquals($expected, $systemMessages);
    }
    public function testReturnsSystemMessages2(): void
    {
        // this attempts to do the above test, which doesn't currently work, but using prophecies instead
        $mockResponse = [
            'Parameters' => [
                ['Name' => '/system-message/use/en', 'Value' => 'English usage message'],
                ['Name' => '/system-message/use/cy', 'Value' => 'Welsh usage message'],
            ],
        ];
        $ssmClientProphecy = $this->prophesize($this->ssmClient::class);
        $ssmClientProphecy->getParameters()
            ->willReturn($mockResponse)->reveal();

        $systemMessageProphecy = $this->prophesize($this->systemMessage::class);

        $systemMessages = $systemMessageProphecy->getSystemMessages();

        $expected = [
            '/system-message/use/en' => 'English usage message',
            '/system-message/use/cy' => 'Welsh usage message',
        ];
        $this->assertEquals($expected, $systemMessages);
    }
}
