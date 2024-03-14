<?php

declare(strict_types=1);

namespace App\Service\Features;

use RuntimeException;

class FeatureEnabled
{
    /**
     * @param array<string, mixed> $featureFlags An key value map of feature names to boolean values
     * @codeCoverageIgnore
     */
    public function __construct(private array $featureFlags)
    {
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
