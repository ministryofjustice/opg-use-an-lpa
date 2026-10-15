<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Service\SystemMessage\SystemMessageService;
use Common\View\Twig\RequestGlobalsRuntime;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RequestGlobalsRuntime::class)]
final class RequestGlobalsRuntimeTest extends TestCase
{
    #[Test]
    public function it_should_store_and_reset_user_and_flash_without_loading_system_messages(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::never())->method('getMessages');
        $runtime = new RequestGlobalsRuntime($service);
        $user    = $this->createStub(UserInterface::class);
        $flash   = $this->createStub(FlashMessagesInterface::class);

        self::assertNull($runtime->getUser());
        self::assertNull($runtime->getFlashMessages());

        $runtime->setUser($user);
        $runtime->setFlashMessages($flash);
        self::assertSame($user, $runtime->getUser());
        self::assertSame($flash, $runtime->getFlashMessages());

        $runtime->reset();
        self::assertNull($runtime->getUser());
        self::assertNull($runtime->getFlashMessages());
    }

    #[Test]
    public function it_should_cache_system_messages_until_reset_including_an_empty_result(): void
    {
        $messages = ['use/en' => 'Service message'];
        $service  = $this->createMock(SystemMessageService::class);
        $service->expects(self::exactly(2))->method('getMessages')->willReturnOnConsecutiveCalls(
            $messages,
            [],
        );
        $runtime = new RequestGlobalsRuntime($service);

        self::assertSame($messages, $runtime->getSystemMessages());
        self::assertSame($messages, $runtime->getSystemMessages());

        $runtime->reset();
        self::assertSame([], $runtime->getSystemMessages());
        self::assertSame([], $runtime->getSystemMessages());
    }

    #[Test]
    public function it_should_propagate_system_message_service_errors(): void
    {
        $exception = new RuntimeException('System messages failed');
        $service   = $this->createMock(SystemMessageService::class);
        $service->expects(self::once())->method('getMessages')->willThrowException($exception);
        $runtime = new RequestGlobalsRuntime($service);

        $this->expectExceptionObject($exception);

        $runtime->getSystemMessages();
    }
}
