<?php

declare(strict_types=1);

namespace App;

use Aws;
use Facile;
use GuzzleHttp;
use Laminas;
use Psr;

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
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                // PSR20
                Psr\Clock\ClockInterface::class => Service\InternalClock::class,

                // PSR17
                Psr\Http\Message\RequestFactoryInterface::class => GuzzleHttp\Psr7\HttpFactory::class,
                Psr\Http\Message\StreamFactoryInterface::class  => GuzzleHttp\Psr7\HttpFactory::class,

                // allows scalar value setting on the container at runtime.
                Service\Container\ModifiableContainerInterface::class
                    => Service\Container\PhpDiModifiableContainer::class,

                // Data Access
                DataAccess\Repository\ActorCodesInterface::class => DataAccess\DynamoDb\ActorCodes::class,
                DataAccess\Repository\ActorUsersInterface::class => DataAccess\DynamoDb\ActorUsers::class,
                DataAccess\Repository\ViewerCodeActivityInterface::class
                    => DataAccess\DynamoDb\ViewerCodeActivity::class,
                DataAccess\Repository\ViewerCodesInterface::class     => DataAccess\DynamoDb\ViewerCodes::class,
                DataAccess\Repository\UserLpaActorMapInterface::class => DataAccess\DynamoDb\UserLpaActorMap::class,
                DataAccess\Repository\LpasInterface::class            => DataAccess\ApiGateway\SiriusLpas::class,
                DataAccess\Repository\RequestLetterInterface::class   => DataAccess\ApiGateway\SiriusLpas::class,
                DataAccess\Repository\InstructionsAndPreferencesImagesInterface::class
                    => DataAccess\ApiGateway\InstructionsAndPreferencesImages::class,

                // One Login
                Facile\OpenIDClient\Issuer\IssuerBuilderInterface::class
                    => Facile\OpenIDClient\Issuer\IssuerBuilder::class,

                // System messages
                Service\SystemMessage\SystemMessageService::class => Service\SystemMessage\SystemMessage::class,

                // Secrets
                Service\Secrets\SecretManagerInterface::class => Service\Secrets\LpaDataStoreSecretManager::class,

                // Services
                Service\Lpa\LpaManagerInterface::class => Service\Lpa\LpaService::class,
            ],
            'autowires'  => [
                // these two Managers need explicitly autowiring so that they're recognised
                // when setup in the delegators section. This is a PHP-DI specific configuration
                Service\Secrets\OneLoginIdentityKeyPairManager::class,
                Service\Secrets\LpaDataStoreSecretManager::class,
            ],
            'factories'  => [
                // PSR18
                Psr\Http\Client\ClientInterface::class => Service\ApiClient\ClientFactory::class,

                // Services
                Aws\Sdk::class                                 => Service\Aws\SdkFactory::class,
                Aws\DynamoDb\DynamoDbClient::class             => Service\Aws\DynamoDbClientFactory::class,
                Aws\SecretsManager\SecretsManagerClient::class => Service\Aws\SecretsManagerFactory::class,
                Aws\Ssm\SsmClient::class                       => Service\Aws\SSMClientFactory::class,
                Service\Email\EmailClient::class               => Service\Email\EmailClientFactory::class,
                Service\SystemMessage\SystemMessage::class     => Service\SystemMessage\SystemMessageFactory::class,
                Service\Features\FeatureEnabled::class         => Service\Features\FeatureEnabledFactory::class,
                Service\Lpa\LpaManagerInterface::class         => Service\Lpa\LpaManagerFactory::class,

                // Data Access
                DataAccess\DynamoDb\ActorCodes::class         => DataAccess\DynamoDb\ActorCodesFactory::class,
                DataAccess\DynamoDb\ActorUsers::class         => DataAccess\DynamoDb\ActorUsersFactory::class,
                DataAccess\DynamoDb\ViewerCodeActivity::class => DataAccess\DynamoDb\ViewerCodeActivityFactory::class,
                DataAccess\DynamoDb\ViewerCodes::class        => DataAccess\DynamoDb\ViewerCodesFactory::class,
                DataAccess\DynamoDb\UserLpaActorMap::class    => DataAccess\DynamoDb\UserLpaActorMapFactory::class,
                DataAccess\ApiGateway\SiriusLpas::class       => DataAccess\ApiGateway\SiriusLpasFactory::class,
                DataAccess\ApiGateway\DataStoreLpas::class    => DataAccess\ApiGateway\DataStoreLpasFactory::class,
                DataAccess\ApiGateway\InstructionsAndPreferencesImages::class
                    => DataAccess\ApiGateway\InstructionsAndPreferencesImagesFactory::class,

                // Code Validation
                Service\ActorCodes\CodeValidationStrategyInterface::class
                    => Service\ActorCodes\CodeValidationStrategyFactory::class,
                DataAccess\ApiGateway\ActorCodes::class => DataAccess\ApiGateway\ActorCodesFactory::class,

                // Handlers
                Handler\HealthcheckHandler::class => Handler\Factory\HealthcheckHandlerFactory::class,

                // One Login
                Service\Authentication\AuthorisationClientManager::class
                    => Service\Authentication\AuthorisationClientManagerFactory::class,
            ],
            'delegators' => [
                Laminas\Stratigility\Middleware\ErrorHandler::class   => [
                    Service\Log\LogStderrListenerDelegatorFactory::class,
                ],
                Laminas\Cache\Storage\AdapterPluginManager::class     => [
                    Laminas\Cache\Storage\Adapter\Apcu\AdapterPluginManagerDelegatorFactory::class,
                ],
                Service\Secrets\LpaDataStoreSecretManager::class      => [
                    Service\Secrets\CachedSecretManagerDelegatorFactory::class,
                ],
                Service\Secrets\OneLoginIdentityKeyPairManager::class => [
                    Service\Secrets\CachedKeyPairManagerDelegatorFactory::class,
                ],
                Service\SystemMessage\SystemMessage::class            => [
                    Service\SystemMessage\CachedSystemMessageDelegatorFactory::class,
                ],
            ],
        ];
    }
}
