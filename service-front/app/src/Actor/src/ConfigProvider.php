<?php

declare(strict_types=1);

namespace Actor;

/**
 * The configuration provider for the Actor module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 * @codeCoverageIgnore
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'factories'  => [
                \Actor\Handler\CheckLpaHandler::class => \Actor\Handler\Factory\CheckLpaHandlerFactory::class,
                \Actor\Handler\LoginPageHandler::class => \Actor\Handler\Factory\LoginPageHandlerFactory::class,
                \Actor\Handler\ActorSessionCheckHandler::class => \Actor\Handler\Factory\ActorSessionCheckHandlerFactory::class
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'actor' => [__DIR__ . '/../templates/actor'],
            ],
        ];
    }
}
