<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RequestGlobalsExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('user', [RequestGlobalsRuntime::class, 'getUser']),
            new TwigFunction('flash', [RequestGlobalsRuntime::class, 'getFlashMessages']),
            new TwigFunction('system_messages', [RequestGlobalsRuntime::class, 'getSystemMessages']),
        ];
    }
}
