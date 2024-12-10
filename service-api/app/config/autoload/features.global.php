<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_legacy_codes_service' => getenv('USE_LEGACY_CODES_SERVICE') ?: 'false',
        'allow_meris_lpas' => filter_var(getenv('ALLOW_MERIS_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'support_datastore_lpas' => filter_var(
            getenv('SUPPORT_DATASTORE_LPAS'),
            FILTER_VALIDATE_BOOLEAN
        )
    ],
];
