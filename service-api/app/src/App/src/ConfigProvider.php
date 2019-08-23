<?php

declare(strict_types=1);

namespace App;

use Aws;
use Http;
use Psr;
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
                Http\Client\HttpClient::class => Http\Adapter\Guzzle6\Client::class,

                // Data Access
                DataAccess\Repository\ActorLpaCodesInterface::class => DataAccess\DynamoDb\ActorLpaCodes::class,
                DataAccess\Repository\ActorUsersInterface::class => DataAccess\DynamoDb\ActorUsers::class,
                DataAccess\Repository\ViewerCodeActivityInterface::class => DataAccess\DynamoDb\ViewerCodeActivity::class,
                DataAccess\Repository\ViewerCodesInterface::class => DataAccess\DynamoDb\ViewerCodes::class,
            ],

            'factories'  => [
                // Services
                Aws\Sdk::class => Service\Aws\SdkFactory::class,
                Aws\DynamoDb\DynamoDbClient::class => Service\Aws\DynamoDbClientFactory::class,
                Service\ApiClient\Client::class => Service\ApiClient\ClientFactory::class,

                // Data Access
                DataAccess\DynamoDb\ActorLpaCodes::class => DataAccess\DynamoDb\ActorLpaCodesFactory::class,
                DataAccess\DynamoDb\ActorUsers::class => DataAccess\DynamoDb\ActorUsersFactory::class,
                DataAccess\DynamoDb\ViewerCodeActivity::class => DataAccess\DynamoDb\ViewerCodeActivityFactory::class,
                DataAccess\DynamoDb\ViewerCodes::class => DataAccess\DynamoDb\ViewerCodesFactory::class,

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
}
