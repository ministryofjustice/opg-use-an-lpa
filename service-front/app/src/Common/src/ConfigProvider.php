<?php

declare(strict_types=1);

namespace Common;

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\TranslatorInterface;
use Aws\Kms\KmsClient;
use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use Common\Middleware\Authentication\AuthenticationMiddleware;
use Common\Middleware\Authentication\AuthenticationMiddlewareFactory;
use Common\Middleware\Session\{SessionExpiryMiddleware, SessionExpiryMiddlewareFactory};
use Common\Service\{Cache\RedisAdapterPluginManagerDelegatorFactory,
    SystemMessage\SystemMessageService,
    SystemMessage\SystemMessageServiceFactory};
use Gettext\Generator\{GeneratorInterface, PoGenerator};
use Gettext\Loader\{LoaderInterface, PoLoader};
use GuzzleHttp\Client;
use Laminas\Cache\Storage\Adapter\Memory\AdapterPluginManagerDelegatorFactory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Stratigility\{Middleware\ErrorHandler, MiddlewarePipe, MiddlewarePipeInterface};
use Mezzio\Authentication\{AuthenticationInterface, Session\PhpSession, UserInterface, UserRepositoryInterface};
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Session\{SessionMiddleware, SessionMiddlewareFactory, SessionPersistenceInterface};
use Psr\Http\Client\ClientInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

/**
 * The configuration provider for the Common module
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
            'twig'         => $this->getTwig(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                ClientInterface::class => Client::class,
                Service\Session\Encryption\EncryptInterface::class
                    => Service\Session\Encryption\KmsEncryptedCookie::class,
                SessionPersistenceInterface::class => Service\Session\EncryptedCookiePersistence::class,

                // Custom Guard factory to handle multiple forms per page
                CsrfGuardFactoryInterface::class => Service\Csrf\SessionCsrfGuardFactory::class,

                // The Session Key Manager to use
                Service\Session\KeyManager\KeyManagerInterface::class => Service\Session\KeyManager\KmsManager::class,

                // Auth
                UserRepositoryInterface::class => Service\User\UserService::class,
                AuthenticationInterface::class => PhpSession::class,
                MiddlewarePipeInterface::class => MiddlewarePipe::class,

                // allows value setting on the container at runtime.
                Service\Container\ModifiableContainerInterface::class
                    => Service\Container\PhpDiModifiableContainer::class,
                Service\Lpa\LpaFactory::class               => Service\Lpa\Factory\Sirius::class,
                Service\Lpa\InstAndPrefImagesFactory::class => Service\Lpa\Factory\InstAndPrefImages::class,

                // Language extraction
                LoaderInterface::class    => PoLoader::class,
                GeneratorInterface::class => PoGenerator::class,
            ],
            'factories'  => [
                // Services
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
                Service\Pdf\PdfService::class   => Service\Pdf\PdfServiceFactory::class,
                Service\Session\EncryptedCookiePersistence::class
                    => Service\Session\EncryptedCookiePersistenceFactory::class,
                Service\Session\KeyManager\KmsManager::class => Service\Session\KeyManager\KmsManagerFactory::class,
                Service\User\UserService::class              => Service\User\UserServiceFactory::class,
                Service\Features\FeatureEnabled::class       => Service\Features\FeatureEnabledFactory::class,
                Service\Session\Encryption\KmsEncryptedCookie::class
                    => Service\Session\Encryption\KmsEncryptedCookieFactory::class,
                Sdk::class                  => Service\Aws\SdkFactory::class,
                KmsClient::class            => Service\Aws\KmsFactory::class,
                SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,
                Client::class               => Service\ApiClient\GuzzleClientFactory::class,
                SystemMessageService::class => SystemMessageServiceFactory::class,

                // Middleware
                SessionMiddleware::class                   => SessionMiddlewareFactory::class,
                SessionExpiryMiddleware::class             => SessionExpiryMiddlewareFactory::class,
                Middleware\I18n\SetLocaleMiddleware::class => Middleware\I18n\SetLocaleMiddlewareFactory::class,
                AuthenticationMiddleware::class            => AuthenticationMiddlewareFactory::class,

                // Auth
                UserInterface::class                    => Entity\UserFactory::class,
                Service\OneLogin\OneLoginService::class => Service\OneLogin\OneLoginServiceFactory::class,

                // Handlers
                Handler\CookiesPageHandler::class => Handler\Factory\CookiesPageHandlerFactory::class,
                Handler\HealthcheckHandler::class => Handler\Factory\HealthcheckHandlerFactory::class,
                TranslatorInterface::class        => I18n\TranslatorFactory::class,
                TranslationExtension::class
                    => View\Twig\TranslationExtensionFactory::class,
                View\Twig\JavascriptVariablesExtension::class => View\Twig\JavascriptVariablesExtensionFactory::class,
                View\Twig\GenericGlobalVariableExtension::class
                    => View\Twig\GenericGlobalVariableExtensionFactory::class,
            ],
            'delegators' => [
                ErrorHandler::class         => [
                    Service\Log\LogStderrListenerDelegatorFactory::class,
                ],
                AdapterPluginManager::class => [
                    AdapterPluginManagerDelegatorFactory::class,
                    RedisAdapterPluginManagerDelegatorFactory::class,
                ],
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
                'error'    => [__DIR__ . '/../templates/error'],
                'layout'   => [__DIR__ . '/../templates/layout'],
                'partials' => [__DIR__ . '/../templates/partials'],
                'common'   => [__DIR__ . '/../templates/common'],
            ],
        ];
    }

    public function getTwig(): array
    {
        return [
            'extensions'      => [
                TranslationExtension::class,
                View\Twig\LpaExtension::class,
                View\Twig\OrdinalNumberExtension::class,
                View\Twig\GovUKLaminasFormErrorsExtension::class,
                View\Twig\GovUKLaminasFormExtension::class,
                View\Twig\JavascriptVariablesExtension::class,
                View\Twig\GenericGlobalVariableExtension::class,
                View\Twig\TranslationSwitchExtension::class,
                View\Twig\FeatureFlagExtension::class,
            ],
            'runtime_loaders' => [
                ContainerRuntimeLoader::class,
            ],
        ];
    }
}
