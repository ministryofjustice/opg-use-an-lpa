<?php

declare(strict_types=1);

namespace CommonTest\Service\SystemMessage;

use PHPUnit\Framework\Attributes\Test;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\SystemMessage\SystemMessageService;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SystemMessageServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    #[Test]
    public function get_messages_calls_api(): void
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/v1/system-message')->shouldBeCalled()->willReturn([
            'use/en' => 'English',
            'use/cy' => 'Welsh',
        ]);

        $systemMessageService = new SystemMessageService($apiClientProphecy->reveal());

        $messages = $systemMessageService->getMessages();

        $this->assertEquals('English', $messages['use/en'] ?? null);
        $this->assertEquals('Welsh', $messages['use/cy'] ?? null);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function gets_no_messages_when_api_fails(): void
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet('/v1/system-message')->shouldBeCalled()->willThrow(ApiException::class);

        $systemMessageService = new SystemMessageService($apiClientProphecy->reveal());

        $messages = $systemMessageService->getMessages();

        $this->assertEmpty($messages);
    }
}
