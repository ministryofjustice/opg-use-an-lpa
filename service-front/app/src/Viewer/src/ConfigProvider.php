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
            'twig'         => $this->getTwig()
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
                Zend\Expressive\Session\SessionPersistenceInterface::class => Service\Session\EncryptedCookie::class,

                // Twig
                Symfony\Component\Form\FormRenderer::class => Symfony\Component\Form\FormRendererInterface::class,

                // Forms
                Symfony\Component\Form\FormFactoryInterface::class => Symfony\Component\Form\FormFactory::class,
                Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class => Middleware\Csrf\TokenManager::class,
            ],

            'invokables' => [
                // Handlers

                // Services
                Service\Session\KeyManager\KeyCache::class,
            ],

            'factories'  => [

                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,

                Http\Adapter\Guzzle6\Client::class => Service\Http\GuzzleClientFactory::class,

                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
                Service\Lpa\LpaService::class => Service\Lpa\LpaServiceFactory::class,

                // Twig
                Symfony\Bridge\Twig\Extension\TranslationExtension::class => Service\Twig\TranslationExtensionFactory::class,
                Twig\RuntimeLoader\ContainerRuntimeLoader::class => Service\Twig\ContainerRuntimeLoaderFactory::class,
                Symfony\Component\Form\FormRendererInterface::class => Service\Twig\FormRendererFactory::class,
                Symfony\Component\Form\FormRendererEngineInterface::class => Service\Twig\FormRendererEngineFactory::class,

                // Forms
                Middleware\Csrf\TokenManagerMiddleware::class => Middleware\Csrf\TokenManagerMiddlewareFactory::class,
                Symfony\Component\Form\FormFactory::class => Service\Form\FormFactory::class,
                Middleware\Csrf\TokenManager::class => Middleware\Csrf\TokenManagerFactory::class,

                Service\Session\EncryptedCookie::class => Service\Session\EncryptedCookieFactory::class,
                Service\Session\KeyManager\Manager::class => Service\Session\KeyManager\ManagerFactory::class,

                Zend\Expressive\Session\SessionMiddleware::class => Zend\Expressive\Session\SessionMiddlewareFactory::class,
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
        $reflector = new ReflectionClass(ClassLoader::class);
        $file      = $reflector->getFileName();
        $vendorDir = realpath(dirname($file) . '/../');

        return [
            'paths' => [
                $vendorDir . '/symfony/twig-bridge/Resources/views/Form',
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
            'form_themes' => [
                '@partials/govuk_form.html.twig'
            ],
            'extensions' => [
                View\Twig\OrdinalNumberExtension::class,
                Symfony\Bridge\Twig\Extension\CsrfExtension::class,
                Symfony\Bridge\Twig\Extension\FormExtension::class,
                Symfony\Bridge\Twig\Extension\TranslationExtension::class
            ],
            'runtime_loaders' => [
                Twig\RuntimeLoader\ContainerRuntimeLoader::class
            ]
        ];
    }
}
