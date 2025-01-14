<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', FeatureFlagRuntime::class . '::featureEnabled'),
        ];
    }
}
