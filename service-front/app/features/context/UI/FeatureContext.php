<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use DI\Definition\Helper\FactoryDefinitionHelper;

class FeatureContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /**
     * @BeforeScenario @allowOlderLpasOn
     */
    public function allowOlderLpasOn(BeforeScenarioScope $scope)
    {
        $this->gatherContexts($scope);
        $config = $this->base->container->get('config');
        $config['feature_flags']['allow_older_lpas'] = true;
        $this->base->container->set('config', $config);
        $this->base->container->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($this->base->container->get(FeatureEnabledFactory::class))
        );
    }

    /**
     * @BeforeScenario @allowOlderLpasOff
     */
    public function allowOlderLpasOff(BeforeScenarioScope $scope)
    {
        $this->gatherContexts($scope);
        $config = $this->base->container->get('config');
        $config['feature_flags']['allow_older_lpas'] = "test";
        $this->base->container->set('config', $config);
        $this->base->container->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($this->base->container->get(FeatureEnabledFactory::class))
        );
    }

    /**
     * @BeforeScenario @saveOlderLpaRequestsOn
     */
    public function saveOlderLpaRequestsOn(BeforeScenarioScope $scope)
    {
        $this->gatherContexts($scope);
        $config = $this->base->container->get('config');
        $config['feature_flags']['save_older_lpa_requests'] = true;
        $this->base->container->set('config', $config);
        $this->base->container->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($this->base->container->get(FeatureEnabledFactory::class))
        );
    }

    /**
     * @BeforeScenario @saveOlderLpaRequestsOff
     */
    public function saveOlderLpaRequestsOff(BeforeScenarioScope $scope)
    {
        $this->gatherContexts($scope);
        $config = $this->base->container->get('config');
        $config['feature_flags']['save_older_lpa_requests'] = false;
        $this->base->container->set('config', $config);
        $this->base->container->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($this->base->container->get(FeatureEnabledFactory::class))
        );
    }
}
