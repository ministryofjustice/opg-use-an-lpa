<?php

declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
                Handler\PingHandler::class => Handler\PingHandler::class,
            ],

            'factories'  => [
                Handler\HomePageHandler::class => Handler\HomePageHandlerFactory::class,
                Handler\EnterCodeHandler::class => Handler\EnterCodeHandlerFactory::class,
            ] + $this->getConfigs(),

            'autowires' => [
                Middleware\Session\General::class,

                Service\Session\Cookie::class,
                Service\Session\KeyManager\Manager::class,
            ],
        ];
    }

    public function getConfigs() : array
    {
        return [

            // For Session key manager
            Service\Session\KeyManager\Config::class => function(ContainerInterface $container){
                return new Service\Session\KeyManager\Config($container->get('config')['session']['key']);
            },

        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates() : array
    {
        return [
            'paths' => [
                'app'    => [__DIR__ . '/../templates/app'],
                'error'  => [__DIR__ . '/../templates/error'],
                'layout' => [__DIR__ . '/../templates/layout'],
            ],
        ];
    }
}
