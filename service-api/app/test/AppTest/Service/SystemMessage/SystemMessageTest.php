<?php

declare(strict_types=1);

namespace Service\SystemMessage;

use App\Service\SystemMessage\SystemMessage;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function returnsSystemMessages(): void
    {
        $mockResponse = [
            'Parameters' => [
                ['Name' => '/system-message/use/en', 'Value' => 'English usage message'],
                ['Name' => '/system-message/use/cy', 'Value' => 'Welsh usage message'],
            ],
        ];

        // this test can be deleted once the 2cnd version using prophecy below, is working , as it will replace this
        $this->ssmClient->method('__call')
            ->with($this->identicalTo('getParameters'))
            ->willReturn($mockResponse);


        $systemMessages = $this->systemMessage->getSystemMessages();


        $expected = [
            '/system-message/use/en' => 'English usage message',
            '/system-message/use/cy' => 'Welsh usage message',
        ];
        $this->assertEquals($expected, $systemMessages);
    }
}
