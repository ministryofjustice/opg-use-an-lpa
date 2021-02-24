<?php

declare(strict_types=1);

namespace Common\Service\Features;

use RuntimeException;

class FeatureEnabled
{
    private array $featureFlags;

    public function __construct(array $featureFlags)
    {
        $this->featureFlags = $featureFlags;
    }

    public function __invoke(string $featureName): bool
    {
        if (!array_key_exists($featureName, $this->featureFlags)) {
            throw new RuntimeException('Feature flag "' . $featureName . '" is not currently configured');
        }

        return  $this->featureFlags[$featureName];
    }
}
