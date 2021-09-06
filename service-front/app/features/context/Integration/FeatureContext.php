<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use BehatTest\Context\ActorContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use DI\Definition\Helper\FactoryDefinitionHelper;

class FeatureContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    /**
     * @BeforeScenario @allowOlderLpasOn
     */
    public function allowOlderLpasOn(BeforeScenarioScope $scope)
    {
        $lpaContextContainer = $scope->getEnvironment()->getContext(LpaContext::class)->container;
        $config = $lpaContextContainer->get('config');
        $config['feature_flags']['allow_older_lpas'] = true;
        $lpaContextContainer->set('config', $config);
    }

    /**
     * @BeforeScenario @allowOlderLpasOff
     */
    public function allowOlderLpasOff(BeforeScenarioScope $scope)
    {
        $lpaContextContainer = $scope->getEnvironment()->getContext(LpaContext::class)->container;
        $config = $lpaContextContainer->get('config');
        $config['feature_flags']['allow_older_lpas'] = false;
        $lpaContextContainer->set('config', $config);

        $lpaContextContainer->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($lpaContextContainer->get(FeatureEnabledFactory::class))
        );
    }

    /**
     * @BeforeScenario @saveOlderLpaRequestsOn
     */
    public function saveOlderLpaRequestsOn(BeforeScenarioScope $scope)
    {
        $lpaContextContainer = $scope->getEnvironment()->getContext(LpaContext::class)->container;
        $config = $lpaContextContainer->get('config');
        $config['feature_flags']['save_older_lpa_requests'] = true;
        $lpaContextContainer->set('config', $config);
        $lpaContextContainer->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($lpaContextContainer->get(FeatureEnabledFactory::class))
        );
    }

    /**
     * @BeforeScenario @saveOlderLpaRequestsOff
     */
    public function saveOlderLpaRequestsOff(BeforeScenarioScope $scope)
    {
        $lpaContextContainer = $scope->getEnvironment()->getContext(LpaContext::class)->container;
        $config = $lpaContextContainer->get('config');
        $config['feature_flags']['save_older_lpa_requests'] = false;
        $lpaContextContainer->set('config', $config);
        $lpaContextContainer->set(
            FeatureEnabled::class,
            new FactoryDefinitionHelper($lpaContextContainer->get(FeatureEnabledFactory::class))
        );
    }

    protected function prepareContext(): void
    {
        // TODO: Implement prepareContext() method.
    }
}
