<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\Features\FeatureEnabled;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    private FeatureEnabled $featureEnabled;

    public function __construct(FeatureEnabled $featureEnabled)
    {
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [$this, 'featureEnabled'])
        ];
    }

    public function featureEnabled(string $featureName): bool
    {
        return ($this->featureEnabled)($featureName);
    }
}
