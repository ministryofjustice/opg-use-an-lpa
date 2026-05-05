<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'allow_meris_lpas'       => filter_var(getenv('ALLOW_MERIS_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'support_datastore_lpas' => filter_var(
            getenv('SUPPORT_DATASTORE_LPAS'),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],
];
