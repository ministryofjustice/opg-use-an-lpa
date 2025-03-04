<?php

declare(strict_types=1);

use App\Service\ApiClient\ClientFactory;
use Psr\Http\Client\ClientInterface;

return [
    'dependencies'   => [
        'factories' => [
            ClientInterface::class => ClientFactory::class,
        ],
    ],
    'sirius_api'     => [
        'endpoint' => 'http://localhost:9191',
    ],
    'codes_api'      => [
        'endpoint'          => 'http://localhost:9090',
        'static_auth_token' => getenv('LPA_CODES_STATIC_AUTH_TOKEN') ?: null,
    ],
    'iap_images_api' => [
        'endpoint' => 'http://localhost:9292',
    ],
];
