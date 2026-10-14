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
        // by making these functions rather than true global variables we gain two benefits
        //  * we can cache the results of the function call in the runtime class
        //  * we don't have to worry about twig caching so much
        return [
            new TwigFunction('user', [RequestGlobalsRuntime::class, 'getUser']),
            new TwigFunction('flash', [RequestGlobalsRuntime::class, 'getFlashMessages']),
            new TwigFunction('system_messages', [RequestGlobalsRuntime::class, 'getSystemMessages']),
        ];
    }
}
