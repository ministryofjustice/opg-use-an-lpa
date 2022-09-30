<?php

declare(strict_types=1);

namespace Viewer;

use Common\Handler\Factory\HealthcheckHandlerFactory;
use Common\Handler\HealthcheckHandler;
use Viewer\Handler\CheckCodeHandler;
use Viewer\Handler\Factory\CheckCodeHandlerFactory;
use Viewer\Handler\Factory\ViewerSessionCheckHandlerFactory;
use Viewer\Handler\ViewerSessionCheckHandler;

/**
 * The configuration provider for the App module
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
                // Handlers
                HealthcheckHandler::class        => HealthcheckHandlerFactory::class,
                CheckCodeHandler::class          => CheckCodeHandlerFactory::class,
                ViewerSessionCheckHandler::class => ViewerSessionCheckHandlerFactory::class,
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
                'viewer' => [__DIR__ . '/../templates/viewer'],
            ],
        ];
    }
}
