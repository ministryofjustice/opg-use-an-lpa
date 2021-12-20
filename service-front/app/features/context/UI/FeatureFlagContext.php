<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Features\FeatureEnabledFactory;
use Common\View\Twig\FeatureFlagExtension;
use DI\Definition\Helper\CreateDefinitionHelper;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Exception;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigEnvironmentFactory;
use Mezzio\Twig\TwigRenderer;
use Mezzio\Twig\TwigRendererFactory;
use Twig\Environment;

class FeatureFlagContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

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

                if (!preg_match('/^[a-z_0-9]+$/', $tagParts[1], $matches)) {
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

                $featureFlagExtensionHandler = new CreateDefinitionHelper(FeatureFlagExtension::class);
                $featureFlagExtensionHandler->constructor($this->base->container->get(FeatureEnabled::class));

                $this->base->container->set(
                    FeatureFlagExtension::class,
                    $featureFlagExtensionHandler
                );

                $this->resetTwigEnvironments();
            }
        }
    }

    private function resetTwigEnvironments(): void
    {
        $this->base->container->set(
            Environment::class,
            new FactoryDefinitionHelper($this->base->container->get(TwigEnvironmentFactory::class))
        );

        $this->base->container->set(
            TwigRenderer::class,
            new FactoryDefinitionHelper($this->base->container->get(TwigRendererFactory::class))
        );

        $this->base->container->set(
            TemplateRendererInterface::class,
            new FactoryDefinitionHelper($this->base->container->get(TwigRendererFactory::class))
        );
    }
}
