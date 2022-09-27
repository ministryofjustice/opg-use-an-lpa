<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\Features\FeatureEnabled;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    /**
     * @param FeatureEnabled $featureEnabled
     * @codeCoverageIgnore
     */
    public function __construct(private FeatureEnabled $featureEnabled)
    {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [$this, 'featureEnabled']),
        ];
    }

    /**
     * Returns the enabled state of a feature
     *
     * @param string $featureName The name of a feature configured in features.php
     * @return bool
     */
    public function featureEnabled(string $featureName): bool
    {
        return ($this->featureEnabled)($featureName);
    }
}
