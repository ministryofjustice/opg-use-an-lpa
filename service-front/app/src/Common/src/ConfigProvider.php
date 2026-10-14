<?php

declare(strict_types=1);

namespace Common;

use Acpr\I18n\{TranslationExtension, TranslatorInterface};
use Aws\Kms\KmsClient;
use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use Common\Entity\UserFactory;
use Common\Handler\{
    CookiesPageHandler,
    Factory\CookiesPageHandlerFactory,
    Factory\HealthcheckHandlerFactory,
    HealthcheckHandler
};
use Common\I18n\TranslatorFactory;
use Common\Middleware\{
    I18n\SetLocaleMiddleware,
    I18n\SetLocaleMiddlewareFactory,
    Security\CSPMiddleware,
    Security\CSPMiddlewareFactory,
    Session\SessionExpiryMiddleware,
    Session\SessionExpiryMiddlewareFactory
};
use Common\Service\{
    ApiClient\Client,
    ApiClient\ClientFactory,
    ApiClient\GuzzleClientFactory,
    Aws\KmsFactory,
    Aws\SdkFactory,
    Aws\SecretsManagerFactory,
    Container\ModifiableContainerInterface,
    Container\PhpDiModifiableContainer,
    Csrf\SessionCsrfGuardFactory,
    Features\FeatureEnabled,
    Features\FeatureEnabledFactory,
    Log\LogStderrListenerDelegatorFactory,
    Lpa\InstAndPrefImagesFactory,
    Lpa\LpaFactory,
    OneLogin\OneLoginService,
    OneLogin\OneLoginServiceFactory,
    Pdf\PdfService,
    Pdf\PdfServiceFactory,
    Session\EncryptedCookiePersistence,
    Session\EncryptedCookiePersistenceFactory,
    SystemMessage\SystemMessageService,
    SystemMessage\SystemMessageServiceFactory,
    User\UserService,
    User\UserServiceFactory
};
use Common\Service\Cache\Symfony\{PoolFactory, SimpleCacheFactory, TagAwareFactory};
use Common\Service\Lpa\{Factory\InstAndPrefImages, Factory\Sirius};
use Common\Service\Session\{
    Encryption\EncryptInterface,
    Encryption\EncryptionFallbackCookie,
    Encryption\EncryptionFallbackCookieFactory,
    KeyManager\KeyManagerInterface,
    KeyManager\KmsManager,
    KeyManager\KmsManagerFactory
};
use Common\View\Twig\{
    FeatureFlagExtension,
    GenericGlobalVariableExtension,
    GenericGlobalVariableExtensionFactory,
    GovUKLaminasFormErrorsExtension,
    GovUKLaminasFormExtension,
    JavascriptVariablesExtension,
    JavascriptVariablesExtensionFactory,
    LpaExtension,
    OrdinalNumberExtension,
    TranslationExtensionFactory,
    TranslationSwitchExtension
};
use Gettext\{Generator\GeneratorInterface, Generator\PoGenerator, Loader\LoaderInterface, Loader\PoLoader};
use GuzzleHttp\Client as GuzzleClient;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Authentication\UserInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Symfony\Contracts\Cache\{CacheInterface, TagAwareCacheInterface};
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
                // PSR6/16
                CacheItemPoolInterface::class       => 'cache.request',
                CacheInterface::class               => 'cache.request',
                TagAwareCacheInterface::class       => 'cache.request.taggable',

                ClientInterface::class              => GuzzleClient::class,
                EncryptInterface::class             => EncryptionFallbackCookie::class,
                SessionPersistenceInterface::class  => EncryptedCookiePersistence::class,

                // Custom Guard factory to handle multiple forms per page
                CsrfGuardFactoryInterface::class    => SessionCsrfGuardFactory::class,

                // The Session Key Manager to use
                KeyManagerInterface::class          => KmsManager::class,

                // allows value setting on the container at runtime.
                ModifiableContainerInterface::class => PhpDiModifiableContainer::class,
                LpaFactory::class                   => Sirius::class,
                InstAndPrefImagesFactory::class     => InstAndPrefImages::class,

                // Language extraction
                LoaderInterface::class              => PoLoader::class,
                GeneratorInterface::class           => PoGenerator::class,
            ],
            'factories'  => [
                // Cache
                SimpleCacheInterface::class            => SimpleCacheFactory::class,
                'cache.request'                        => PoolFactory::class,
                'cache.request.taggable'               => TagAwareFactory::class,
                'cache.app'                            => PoolFactory::class,
                'cache.app.taggable'                   => TagAwareFactory::class,
                'cache.ratelimits.viewer_code_failure' => PoolFactory::class,
                'cache.ratelimits.actor_code_failure'  => PoolFactory::class,
                'cache.ratelimits.actor_login_failure' => PoolFactory::class,
                'cache.ratelimits.download_lpa'        => PoolFactory::class,

                // Services
                Client::class                          => ClientFactory::class,
                PdfService::class                      => PdfServiceFactory::class,
                EncryptedCookiePersistence::class      => EncryptedCookiePersistenceFactory::class,
                KmsManager::class                      => KmsManagerFactory::class,
                UserService::class                     => UserServiceFactory::class,
                FeatureEnabled::class                  => FeatureEnabledFactory::class,
                EncryptionFallbackCookie::class        => EncryptionFallbackCookieFactory::class,
                Sdk::class                             => SdkFactory::class,
                KmsClient::class                       => KmsFactory::class,
                SecretsManagerClient::class            => SecretsManagerFactory::class,
                GuzzleClient::class                    => GuzzleClientFactory::class,
                SystemMessageService::class            => SystemMessageServiceFactory::class,

                // Middleware
                SessionMiddleware::class               => SessionMiddlewareFactory::class,
                SessionExpiryMiddleware::class         => SessionExpiryMiddlewareFactory::class,
                SetLocaleMiddleware::class             => SetLocaleMiddlewareFactory::class,
                CSPMiddleware::class                   => CSPMiddlewareFactory::class,

                // Auth
                UserInterface::class                   => UserFactory::class,
                OneLoginService::class                 => OneLoginServiceFactory::class,

                // Handlers
                CookiesPageHandler::class              => CookiesPageHandlerFactory::class,
                HealthcheckHandler::class              => HealthcheckHandlerFactory::class,
                TranslatorInterface::class             => TranslatorFactory::class,
                TranslationExtension::class            => TranslationExtensionFactory::class,
                JavascriptVariablesExtension::class    => JavascriptVariablesExtensionFactory::class,
                GenericGlobalVariableExtension::class  => GenericGlobalVariableExtensionFactory::class,
            ],
            'delegators' => [
                ErrorHandler::class => [
                    LogStderrListenerDelegatorFactory::class,
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
                LpaExtension::class,
                OrdinalNumberExtension::class,
                GovUKLaminasFormErrorsExtension::class,
                GovUKLaminasFormExtension::class,
                JavascriptVariablesExtension::class,
                GenericGlobalVariableExtension::class,
                TranslationSwitchExtension::class,
                FeatureFlagExtension::class,
            ],
            'runtime_loaders' => [
                ContainerRuntimeLoader::class,
            ],
        ];
    }
}
