<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            App\DataAccess\ApiGateway\ActorCodes::class =>
                BehatTest\DataAccess\ApiGateway\PactActorCodesFactory::class,
            App\DataAccess\ApiGateway\Lpas::class =>
                BehatTest\DataAccess\ApiGateway\PactLpasFactory::class,
        ],
    ],
];
