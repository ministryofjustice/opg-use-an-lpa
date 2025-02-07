<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;

trait FeatureFlagContextTrait
{
    /**
     * @var bool[]
     */
    public array $featureFlags = [];

    #[BeforeScenario]
    public function setFeatureFlag(BeforeScenarioScope $scope): void
    {
        $this->featureFlags = [];

        $tags = $scope->getScenario()->getTags();
        foreach ($tags as $tag) {
            if (str_contains($tag, 'ff:')) {
                $tagParts = explode(':', $tag);

                $this->featureFlags = array_merge(
                    $this->featureFlags,
                    $this->processTag($scope->getSuite()->getName(), $tagParts)
                );
            }
        }

        if (($tc = count($this->featureFlags)) > 0) {
            printf("Found %d feature flag/s\n", $tc);
            array_walk($this->featureFlags, fn ($v, $k): int => printf('  %s: %s', $k, $v));
        }
    }

    private function processTag(string $suiteName, array $tagParts): array
    {
        if (in_array(preg_match('/^[a-z_0-9]+$/', (string) $tagParts[1], $matches), [0, false], true)) {
            throw new SuiteConfigurationException(
                'Bad feature flag tag name. All tags must be in snake case (' . $tagParts[1] . ')',
                $suiteName,
            );
        }

        if ($tagParts[2] === 'from_env') {
            $tag = sprintf('BEHAT_FF_%s', strtoupper((string) $tagParts[1]));

            $tagParts[2] = getenv($tag) ?: 'no env flag despite requesting one';
        }

        $flagValue = filter_var($tagParts[2], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if (is_null($flagValue)) {
            throw new SuiteConfigurationException(
                'Feature flag (' . $tagParts[1] . ') value must be boolean, found ' . $tagParts[2],
                $suiteName,
            );
        }

        return [$tagParts[1] => $flagValue];
    }
}
