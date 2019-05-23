<?php

declare(strict_types=1);

return [

    'version' => getenv('CONTAINER_VERSION') ?: 'dev',

    'sirius_api' => [
        'endpoint' => getenv('SIRIUS_API_ENDPOINT') ?: 'https://api.dev.sirius.opg.digital/v1/use-an-lpa',
    ],

    'aws' => [
        'region'  => 'eu-west-1',
        'version' => 'latest',

        'DynamoDb' => [
            'endpoint' => getenv('AWS_ENDPOINT_DYNAMODB') ?: null,
        ],
    ],

];
