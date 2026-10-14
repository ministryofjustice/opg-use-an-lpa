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
    private RequestGlobalsRuntime $runtime;

    protected function setUp(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::never())->method('getMessages');
        $this->runtime = new RequestGlobalsRuntime($service);
    }

    #[Test]
    public function it_should_retain_only_the_bound_user_and_clear_it_on_reset(): void
    {
        $user = $this->createStub(UserInterface::class);

        self::assertNull($this->runtime->getUser());
        $this->runtime->setUser($user);
        self::assertSame($user, $this->runtime->getUser());

        $this->runtime->setUser(null);
        self::assertNull($this->runtime->getUser());

        $this->runtime->setUser($user);
        $this->runtime->reset();
        self::assertNull($this->runtime->getUser());
    }

    #[Test]
    public function it_should_load_system_messages_once_per_request_and_reset_between_requests(): void
    {
        $firstMessages = [
            'use/en'  => 'English message',
            'use/cy'  => 'Welsh message',
            'view/en' => 'Viewer message',
        ];
        $service       = $this->createMock(SystemMessageService::class);
        $service->expects(self::exactly(3))->method('getMessages')->willReturnOnConsecutiveCalls(
            $firstMessages,
            [],
            ['use/en' => 'Updated message'],
        );
        $runtime = new RequestGlobalsRuntime($service);
        $runtime->reset();

        self::assertSame($firstMessages, $runtime->getSystemMessages());
        self::assertSame($firstMessages, $runtime->getSystemMessages());

        $runtime->reset();
        self::assertSame([], $runtime->getSystemMessages());
        self::assertSame([], $runtime->getSystemMessages());

        $runtime->reset();
        self::assertSame(['use/en' => 'Updated message'], $runtime->getSystemMessages());
    }

    #[Test]
    public function it_should_propagate_system_message_service_errors(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::once())->method('getMessages')->willThrowException(
            new RuntimeException('System messages failed'),
        );
        $runtime = new RequestGlobalsRuntime($service);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('System messages failed');

        $runtime->getSystemMessages();
    }

    #[Test]
    public function it_should_expose_the_bound_flash_container_without_reading_its_messages(): void
    {
        $first = $this->createMock(FlashMessagesInterface::class);
        $first->expects(self::never())->method('getFlash');
        $first->expects(self::never())->method('getFlashes');
        $second = $this->createMock(FlashMessagesInterface::class);
        $second->expects(self::never())->method('getFlash');
        $second->expects(self::never())->method('getFlashes');

        self::assertNull($this->runtime->getFlashMessages());

        $this->runtime->setFlashMessages($first);
        self::assertSame($first, $this->runtime->getFlashMessages());

        $this->runtime->setFlashMessages($second);
        self::assertSame($second, $this->runtime->getFlashMessages());

        $this->runtime->setFlashMessages(null);
        self::assertNull($this->runtime->getFlashMessages());

        $this->runtime->setFlashMessages($first);
        $this->runtime->reset();
        self::assertNull($this->runtime->getFlashMessages());
    }
}
