<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;

class FeatureFlagContext implements Context
{
    use BaseUiContextTrait;

    #[BeforeScenario]
    public function setFeatureFlag(BeforeScenarioScope $scope): void
    {
        // ensure that the BaseUIContext upon which all Context work is initialised.
        $this->gatherContexts($scope);

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

                $config                                = $this->base->container->get('config');
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
