<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey'        => filter_var(
            getenv('USE_OLDER_LPA_JOURNEY'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'delete_lpa_feature'           => filter_var(
            getenv('DELETE_LPA_FEATURE'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'allow_meris_lpas'             => filter_var(
            getenv('ALLOW_MERIS_LPAS'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'support_datastore_lpas'       => filter_var(
            getenv('SUPPORT_DATASTORE_LPAS'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'paper_verification'           => filter_var(
            getenv('PAPER_VERIFICATION'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
    ],
];
