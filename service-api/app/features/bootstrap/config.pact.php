<?php

declare(strict_types=1);

use App\DataAccess\ApiGateway\ActorCodes;
use BehatTest\DataAccess\ApiGateway\PactActorCodesFactory;
use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use BehatTest\DataAccess\ApiGateway\PactInstructionsAndPreferencesImagesFactory;
use App\DataAccess\ApiGateway\Lpas;
use BehatTest\DataAccess\ApiGateway\PactLpasFactory;

return [
    'dependencies' => [
        'factories' => [
            ActorCodes::class
                => PactActorCodesFactory::class,
            InstructionsAndPreferencesImages::class
                => PactInstructionsAndPreferencesImagesFactory::class,
            Lpas::class
                => PactLpasFactory::class,
        ],
    ],
];
