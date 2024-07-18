<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey'                                      => filter_var(
            getenv('USE_OLDER_LPA_JOURNEY'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'delete_lpa_feature'                                         => filter_var(
            getenv('DELETE_LPA_FEATURE'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'allow_meris_lpas'                                           => filter_var(
            getenv('ALLOW_MERIS_LPAS'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'instructions_and_preferences'                               => filter_var(
            getenv('INSTRUCTIONS_AND_PREFERENCES'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
        'allow_gov_one_login'                                        => filter_var(
            getenv('ALLOW_GOV_ONE_LOGIN'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
    ],
];
