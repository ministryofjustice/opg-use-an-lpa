<?php

declare(strict_types=1);

namespace Common\Service\Features;

use RuntimeException;

class FeatureEnabled
{
    private array $featureFlags;

    /**
     * FeatureEnabled constructor.
     *
     * @param array $featureFlags An key value map of feature names to boolean values
     *
     * @codeCoverageIgnore
     */
    public function __construct(array $featureFlags)
    {
        $this->featureFlags = $featureFlags;
    }

    public function __invoke(string $featureName): bool
    {
        if (!array_key_exists($featureName, $this->featureFlags)) {
            throw new RuntimeException('Feature flag "' . $featureName . '" is not currently configured');
        }

        if (!is_bool($this->featureFlags[$featureName])) {
            throw new RuntimeException('Feature flag "' . $featureName . '" is not a boolean value');
        }

        return  $this->featureFlags[$featureName];
    }
}
