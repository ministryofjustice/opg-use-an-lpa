<?php

declare(strict_types=1);
return [
    'dependencies' => [
        'factories' => [
            Psr\Http\Client\ClientInterface::class => App\Service\ApiClient\ClientFactory::class,
            App\DataAccess\ApiGateway\ActorCodes::class
                => BehatTest\DataAccess\ApiGateway\PactActorCodesFactory::class,
            App\DataAccess\ApiGateway\InstructionsAndPreferencesImages::class
                => BehatTest\DataAccess\ApiGateway\PactInstructionsAndPreferencesImagesFactory::class,
            App\DataAccess\ApiGateway\Lpas::class
                => BehatTest\DataAccess\ApiGateway\PactLpasFactory::class,
        ],
    ],
];
