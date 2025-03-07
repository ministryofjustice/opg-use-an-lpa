<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Service\Features\FeatureEnabled;
use App\Service\Features\FeatureEnabledFactory;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;

class FeatureFlagContext extends BaseIntegrationContext
{
    #[BeforeScenario]
    public function setFeatureFlag(BeforeScenarioScope $scope): void
    {
        $tags = array_merge($scope->getScenario()->getTags(), $scope->getFeature()->getTags());
        foreach ($tags as $tag) {
            if (str_contains($tag, 'ff:')) {
                $tagParts = explode(':', $tag);

                if (in_array(preg_match('/^[a-z_0-9]+$/', $tagParts[1], $matches), [0, false], true)) {
                    throw new Exception('Bad tag name. All tags must be in snake case');
                }

                $flagValue = filter_var($tagParts[2], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (is_null($flagValue)) {
                    throw new Exception('Feature flag values must be boolean');
                }

                $container                             = $scope->getEnvironment()->getContext(LpaContext::class)->container;
                $config                                = $container->get('config');
                $config['feature_flags'][$tagParts[1]] = $flagValue;
                $container->set('config', $config);
                $container->set(
                    FeatureEnabled::class,
                    new FactoryDefinitionHelper($this->container->get(FeatureEnabledFactory::class))
                );
            }
        }
    }

    protected function prepareContext(): void
    {
        // Not needed for this context
    }
}
