<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Service\Features\FeatureEnabled;
use App\Service\Features\FeatureEnabledFactory;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DI\Definition\Helper\FactoryDefinitionHelper;

class FeatureContext extends BaseIntegrationContext implements Context
{
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
                    throw new \Exception('Bad tag name. All tags must be in snake case');
                }
                $flagValue = filter_var($tagParts[2], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (is_null($flagValue)) {
                    throw new \Exception('Feature flag values must be boolean');
                }

                $container = $scope->getEnvironment()->getContext(LpaContext::class)->container;
                $config = $container->get('config');
                $config['feature_flags'][$tagParts[1]] = $flagValue;
                $container->set('config', $config);
                $this->container->set(
                    FeatureEnabled::class,
                    new FactoryDefinitionHelper($this->container->get(FeatureEnabledFactory::class))
                );
            }
        }
    }

    protected function prepareContext(): void
    {

    }
}
