<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\Features\FeatureEnabled;
use RuntimeException;

class FeatureFlagRuntime
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(private FeatureEnabled $featureEnabled)
    {
    }

    /**
     * Returns the enabled state of a feature
     *
     * @param string $featureName The name of a feature configured in features.php
     * @return bool
     * @throws RuntimeException
     */
    public function featureEnabled(string $featureName): bool
    {
        return ($this->featureEnabled)($featureName);
    }
}
