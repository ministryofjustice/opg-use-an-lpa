<?php

declare(strict_types=1);

namespace Viewer;

use Aws;
use Http;
use Zend;
use Psr;

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
            'twig'         => $this->getTwig(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'aliases' => [
                Psr\Http\Client\ClientInterface::class => Http\Adapter\Guzzle6\Client::class,
                Zend\Expressive\Session\SessionPersistenceInterface::class => Service\Session\EncryptedCookiePersistence::class,

                // The Session Key Manager to use
                Service\Session\KeyManager\KeyManagerInterface::class => Service\Session\KeyManager\KmsManager::class,
            ],

            'factories'  => [

                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\Kms\KmsClient::class => Service\Aws\KmsFactory::class,
                Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,

                Zend\Expressive\Session\SessionMiddleware::class => Zend\Expressive\Session\SessionMiddlewareFactory::class,

                // Config objects
                Service\ApiClient\Config::class => ConfigFactory::class,
                Service\Session\Config::class => ConfigFactory::class,
                Service\Session\KeyManager\Config::class => ConfigFactory::class,

                // Handlers
                Handler\HealthcheckHandler::class => Handler\Factory\HealthcheckHandlerFactory::class
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
                'viewer' => [__DIR__ . '/../templates/viewer'],
            ],
        ];
    }

    public function getTwig() : array
    {
        return [
            'extensions' => [
                View\Twig\OrdinalNumberExtension::class,
                View\Twig\GovUKZendFormErrorsExtension::class,
            ]
        ];
    }
}
