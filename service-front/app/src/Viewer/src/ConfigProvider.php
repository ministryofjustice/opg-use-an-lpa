<?php

declare(strict_types=1);

namespace Viewer;

use Aws;
use Zend;

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
            'aliases' => [
                Zend\Expressive\Session\SessionPersistenceInterface::class => Service\Session\EncryptedCookie::class,
            ],

            'invokables' => [
                // Handlers
                Handler\HomePageHandler::class,
                Handler\EnterCodeHandler::class,

                // Services
                Service\Session\KeyManager\KeyCache::class,
            ],

            'factories'  => [
                // Handlers

                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,

                Service\Session\EncryptedCookie::class => Service\Session\EncryptedCookieFactory::class,
                Service\Session\KeyManager\Manager::class => Service\Session\KeyManager\ManagerFactory::class,

                Zend\Expressive\Session\SessionMiddleware::class => Zend\Expressive\Session\SessionMiddlewareFactory::class,
            ],

            'initializers' => [
                Handler\Initializer\UrlHelperInitializer::class,
                Handler\Initializer\TemplatingSupportInitializer::class,
            ],

            'delegators' => [
                Zend\Stratigility\Middleware\ErrorHandler::class => [
                    Service\Log\LogStderrListenerDelegatorFactory::class,
                ],
            ],
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
