<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\SystemMessage\SystemMessageService;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessagesInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class RequestGlobalsRuntime implements RuntimeExtensionInterface
{
    private ?UserInterface $user                   = null;
    private ?FlashMessagesInterface $flashMessages = null;

    /** @var array<array-key, mixed>|null */
    private ?array $systemMessages = null;

    public function __construct(private SystemMessageService $systemMessageService)
    {
    }

    public function reset(): void
    {
        $this->user           = null;
        $this->flashMessages  = null;
        $this->systemMessages = null;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * @psalm-api
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setFlashMessages(?FlashMessagesInterface $flashMessages): void
    {
        $this->flashMessages = $flashMessages;
    }

    /**
     * @psalm-api
     */
    public function getFlashMessages(): ?FlashMessagesInterface
    {
        return $this->flashMessages;
    }

    /**
     * Loaded on first template access so pages without a banner do not fetch messages.
     *
     * @psalm-api
     * @return array<array-key, mixed>
     */
    public function getSystemMessages(): array
    {
        return $this->systemMessages ??= $this->systemMessageService->getMessages();
    }
}
