<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use BehatTest\Context\ActorContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;

class FeatureFlagContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    /**
     * @BeforeScenario
     */
    public function setFeatureFlag(BeforeScenarioScope $scope)
    {
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

                $lpaContextContainer = $scope->getEnvironment()->getContext(LpaContext::class)->container;
                $config = $lpaContextContainer->get('config');
                $config['feature_flags'][$tagParts[1]] = $flagValue;
                $lpaContextContainer->set('config', $config);
                $lpaContextContainer->set(
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
