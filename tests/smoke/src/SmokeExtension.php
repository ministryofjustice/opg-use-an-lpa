<?php

declare(strict_types=1);

namespace Smoke;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Smoke\Drivers\Driver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SmokeExtension implements Extension
{
    /**
     * @var ServiceProcessor
     */
    protected $processor;

    /**
     * Initializes extension.
     *
     * @param null|ServiceProcessor $processor
     */
    public function __construct(?ServiceProcessor $processor = null)
    {
        $this->processor = $processor ?: new ServiceProcessor();
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey(): string
    {
        return 'smokedriver';
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        $references = $this->processor->findAndSortTaggedServices($container, Driver::DRIVER_TAG);
        $definition = $container->getDefinition('smokedriver.suite_listener');

        foreach ($references as $reference) {
            $definition->addMethodCall('addDriver', [$reference]);
        }
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition('Smoke\Drivers\ChromeDriver');
        $definition->addTag(Driver::DRIVER_TAG);
        $container->setDefinition('smokedriver.driver.chrome', $definition);

        $definition = new Definition('Smoke\DriverSubscriber');
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
        $container->setDefinition('smokedriver.suite_listener', $definition);
    }
}
