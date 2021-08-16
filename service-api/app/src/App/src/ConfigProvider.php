<?php

declare(strict_types=1);

namespace App;

use Aws;
use Http;
use Laminas;
use Psr;

/**
 * The configuration provider for the App module
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
     *
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
            'aliases' => [
                Psr\Http\Client\ClientInterface::class => Http\Adapter\Guzzle6\Client::class,
                Http\Client\HttpClient::class => Http\Adapter\Guzzle6\Client::class,

                // allows value setting on the container at runtime.
                Service\Container\ModifiableContainerInterface::class
                    => Service\Container\PhpDiModifiableContainer::class,

                // Data Access
                DataAccess\Repository\ActorCodesInterface::class => DataAccess\DynamoDb\ActorCodes::class,
                DataAccess\Repository\ActorUsersInterface::class => DataAccess\DynamoDb\ActorUsers::class,
                DataAccess\Repository\ViewerCodeActivityInterface::class
                    => DataAccess\DynamoDb\ViewerCodeActivity::class,
                DataAccess\Repository\ViewerCodesInterface::class => DataAccess\DynamoDb\ViewerCodes::class,
                DataAccess\Repository\UserLpaActorMapInterface::class => DataAccess\DynamoDb\UserLpaActorMap::class,
                DataAccess\Repository\LpasInterface::class => DataAccess\ApiGateway\Lpas::class
            ],

            'factories'  => [
                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\DynamoDb\DynamoDbClient::class => Service\Aws\DynamoDbClientFactory::class,
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,

                // Data Access
                DataAccess\DynamoDb\ActorCodes::class => DataAccess\DynamoDb\ActorCodesFactory::class,
                DataAccess\DynamoDb\ActorUsers::class => DataAccess\DynamoDb\ActorUsersFactory::class,
                DataAccess\DynamoDb\ViewerCodeActivity::class => DataAccess\DynamoDb\ViewerCodeActivityFactory::class,
                DataAccess\DynamoDb\ViewerCodes::class => DataAccess\DynamoDb\ViewerCodesFactory::class,
                DataAccess\DynamoDb\UserLpaActorMap::class => DataAccess\DynamoDb\UserLpaActorMapFactory::class,
                DataAccess\ApiGateway\Lpas::class => DataAccess\ApiGateway\LpasFactory::class,

                // Code Validation
                Service\ActorCodes\CodeValidationStrategyInterface::class
                    => Service\ActorCodes\CodeValidationStrategyFactory::class,
                DataAccess\ApiGateway\ActorCodes::class => DataAccess\ApiGateway\ActorCodesFactory::class,
                DataAccess\ApiGateway\RequestSigner::class => DataAccess\ApiGateway\RequestSignerFactory::class,

                // Handlers
                Handler\HealthcheckHandler::class => Handler\Factory\HealthcheckHandlerFactory::class,

                Service\Features\FeatureEnabled::class => Service\Features\FeatureEnabledFactory::class

            ],

            'delegators' => [
                Laminas\Stratigility\Middleware\ErrorHandler::class => [
                    Service\Log\LogStderrListenerDelegatorFactory::class,
                ],
            ],
        ];
    }
}
