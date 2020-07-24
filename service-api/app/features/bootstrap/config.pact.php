<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            App\DataAccess\ApiGateway\ActorCodes::class =>
                BehatTest\DataAccess\ApiGateway\PactActorCodesFactory::class,
        ],
    ],
];
