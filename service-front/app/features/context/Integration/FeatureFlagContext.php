<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Testwork\Environment\Environment;
use BehatTest\Context\ActorContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;

class FeatureFlagContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    #[BeforeScenario]
    public function setFeatureFlag(BeforeScenarioScope $scope): void
    {
        $tags = array_merge($scope->getScenario()->getTags(), $scope->getFeature()->getTags());
        foreach ($tags as $tag) {
            if (str_contains($tag, 'ff:')) {
                $tagParts = explode(':', $tag);

                if (!preg_match('/^[a-z_0-9]+$/', $tagParts[1], $matches)) {
                    throw new Exception('Bad tag name. All tags must be in snake case');
                }

                $flagValue = filter_var($tagParts[2], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (is_null($flagValue)) {
                    throw new Exception('Feature flag values must be boolean');
                }

                $this->updateContexts($scope->getEnvironment(), $tagParts[1], $flagValue);
            }
        }
    }

    /**
     * @param InitializedContextEnvironment $contextEnvironment
     * @param string                        $flagName
     * @param bool                          $flagValue
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function updateContexts(
        Environment $contextEnvironment,
        string $flagName,
        bool $flagValue,
    ): void {
        /** @var Psr11AwareContext $context */
        foreach ($contextEnvironment->getContexts() as $context) {
            $container = $context->container;

            $config                             = $container->get('config');
            $config['feature_flags'][$flagName] = $flagValue;

            $container->set('config', $config);
            $container->set(
                FeatureEnabled::class,
                new FactoryDefinitionHelper($this->container->get(FeatureEnabledFactory::class))
            );
        }
    }

    protected function prepareContext(): void
    {
        // Not needed for this context
    }
}
