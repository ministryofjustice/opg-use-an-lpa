<?php

declare(strict_types=1);

namespace BehatTest\Common\I18n\Behat;

use Behat\Behat\Tester\ServiceContainer\TesterExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Fails scenarios that render translation keys missing from the active locale's catalog.
 */
final class UntranslatedKeysExtension implements Extension
{
    public function getConfigKey(): string
    {
        return 'untranslated_keys';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(
            UntranslatedKeysStepTester::class,
            [new Reference(TesterExtension::STEP_TESTER_ID)],
        );
        // Between the hookable (9999) and event dispatching (-9999) wrappers so formatters see the failure
        $definition->addTag(TesterExtension::STEP_TESTER_WRAPPER_TAG, ['priority' => 0]);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);

        $container->setDefinition(TesterExtension::STEP_TESTER_WRAPPER_TAG . '.untranslated_keys', $definition);
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
