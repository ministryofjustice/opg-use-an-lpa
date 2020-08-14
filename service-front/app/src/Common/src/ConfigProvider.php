<?php

declare(strict_types=1);

namespace Common;

/**
 * The configuration provider for the Common module
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
            'twig'         => $this->getTwig(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [

            'aliases' => [

                \Psr\Http\Client\ClientInterface::class => \Http\Adapter\Guzzle6\Client::class,
                \Mezzio\Session\SessionPersistenceInterface::class => Service\Session\EncryptedCookiePersistence::class,

                // Custom Guard factory to handle multiple forms per page
                \Mezzio\Csrf\CsrfGuardFactoryInterface::class => Service\Csrf\SessionCsrfGuardFactory::class,

                // The Session Key Manager to use
                Service\Session\KeyManager\KeyManagerInterface::class => Service\Session\KeyManager\KmsManager::class,

                // Auth
                \Mezzio\Authentication\UserRepositoryInterface::class => Service\User\UserService::class,
                \Mezzio\Authentication\AuthenticationInterface::class =>
                    \Mezzio\Authentication\Session\PhpSession::class,

                \Symfony\Contracts\Translation\TranslatorInterface::class =>
                    \Symfony\Component\Translation\Translator::class,

                // allows value setting on the container at runtime.
                Service\Container\ModifiableContainerInterface::class
                    => Service\Container\PhpDiModifiableContainer::class,

                Service\Lpa\LpaFactory::class => Service\Lpa\Factory\Sirius::class
            ],

            'factories'  => [

                // Services
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,
                Service\Pdf\PdfService::class => Service\Pdf\PdfServiceFactory::class,
                Service\Session\EncryptedCookiePersistence::class =>
                    Service\Session\EncryptedCookiePersistenceFactory::class,
                Service\Session\KeyManager\KmsManager::class => Service\Session\KeyManager\KmsManagerFactory::class,
                Service\Email\EmailClient::class => Service\Email\EmailClientFactory::class,
                Service\User\UserService::class => Service\User\UserServiceFactory::class,

                \Aws\Sdk::class => Service\Aws\SdkFactory::class,
                \Aws\Kms\KmsClient::class => Service\Aws\KmsFactory::class,
                \Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,

                // Middleware
                \Mezzio\Session\SessionMiddleware::class => \Mezzio\Session\SessionMiddlewareFactory::class,
                Middleware\I18n\SetLocaleMiddleware::class => Middleware\I18n\SetLocaleMiddlewareFactory::class,

                // Auth
                \Mezzio\Authentication\UserInterface::class => Entity\UserFactory::class,

                // Handlers
                Handler\HealthcheckHandler::class => Handler\Factory\HealthcheckHandlerFactory::class,

                \Symfony\Component\Translation\Translator::class => I18n\SymfonyTranslatorFactory::class,
                \Symfony\Bridge\Twig\Extension\TranslationExtension::class =>
                    View\Twig\SymfonyTranslationExtensionFactory::class,

                View\Twig\JavascriptVariablesExtension::class => View\Twig\JavascriptVariablesExtensionFactory::class,
            ],

            'delegators' => [
                \Laminas\Stratigility\Middleware\ErrorHandler::class => [
                    Service\Log\LogStderrListenerDelegatorFactory::class,
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
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTwig(): array
    {
        return [
            'extensions' => [
                View\Twig\LpaExtension::class,
                View\Twig\OrdinalNumberExtension::class,
                View\Twig\GovUKLaminasFormErrorsExtension::class,
                View\Twig\GovUKLaminasFormExtension::class,
                View\Twig\JavascriptVariablesExtension::class,
                \Symfony\Bridge\Twig\Extension\TranslationExtension::class
            ]
        ];
    }
}
