<?php

declare(strict_types=1);

namespace Viewer;

use Aws;
use Http;
use Composer\Autoload\ClassLoader;
use ReflectionClass;
use Zend;
use Symfony;
use Twig;

use function realpath;
use function dirname;

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
                Http\Client\HttpClient::class => Http\Adapter\Guzzle6\Client::class,
                Zend\Expressive\Session\SessionPersistenceInterface::class => Service\Session\EncryptedCookiePersistence::class,

                // The Session Key Manager to use
                Service\Session\KeyManager\KeyManagerInterface::class => Service\Session\KeyManager\KmsManager::class,
            ],
            
            'factories'  => [

                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\Kms\KmsClient::class => Service\Aws\KmsFactory::class,
                Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,

                Http\Adapter\Guzzle6\Client::class => Service\Http\GuzzleClientFactory::class,

                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
                Service\Lpa\LpaService::class => Service\Lpa\LpaServiceFactory::class,

                Zend\Expressive\Session\SessionMiddleware::class => Zend\Expressive\Session\SessionMiddlewareFactory::class,

                // Config objects
                Service\Session\Config::class => ConfigFactory::class,
                Service\Session\KeyManager\Config::class => ConfigFactory::class,
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
                'app'      => [__DIR__ . '/../templates/app'],
                'error'    => [__DIR__ . '/../templates/error'],
                'layout'   => [__DIR__ . '/../templates/layout'],
                'partials' => [__DIR__ . '/../templates/partials'],
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
