<?php

declare(strict_types=1);

namespace Actor;

use Actor\Handler\ActorSessionCheckHandler;
use Actor\Handler\CheckLpaHandler;
use Actor\Handler\Factory\ActorSessionCheckHandlerFactory;
use Actor\Handler\Factory\CheckLpaHandlerFactory;
use Actor\Handler\Factory\LoginPageHandlerFactory;
use Actor\Handler\Factory\LogoutPageHandlerFactory;
use Actor\Handler\LoginPageHandler;
use Actor\Handler\LogoutPageHandler;

/**
 * The configuration provider for the Actor module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 *
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
            'factories' => [
                CheckLpaHandler::class          => CheckLpaHandlerFactory::class,
                LoginPageHandler::class         => LoginPageHandlerFactory::class,
                LogoutPageHandler::class        => LogoutPageHandlerFactory::class,
                ActorSessionCheckHandler::class => ActorSessionCheckHandlerFactory::class,
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
