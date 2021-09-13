<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use App\Service\Features\FeatureEnabled;
use App\Service\Features\FeatureEnabledFactory;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use BehatTest\Context\BaseAcceptanceContextTrait;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;

class FeatureFlagContext implements Context
{
    use BaseAcceptanceContextTrait;

    /**
     * @BeforeScenario
     */
    public function setFeatureFlag(BeforeScenarioScope $scope)
    {
        $this->gatherContexts($scope);
        $tags = $scope->getScenario()->getTags();
        foreach ($tags as $tag) {
            if (str_contains($tag, 'ff:')) {
                $tagParts = explode(':', $tag);

                if (!preg_match('/^[a-z_]+$/', $tagParts[1], $matches)) {
                    throw new Exception('Bad tag name. All tags must be in snake case');
                }

                $flagValue = filter_var($tagParts[2], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (is_null($flagValue)) {
                    throw new Exception('Feature flag values must be boolean');
                }

                $config = $this->base->container->get('config');
                $config['feature_flags'][$tagParts[1]] = $flagValue;
                $this->base->container->set('config', $config);
                $this->base->container->set(
                    FeatureEnabled::class,
                    new FactoryDefinitionHelper($this->base->container->get(FeatureEnabledFactory::class))
                );
            }
        }
    }
}
