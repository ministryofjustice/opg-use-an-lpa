<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_legacy_codes_service' => getenv('USE_LEGACY_CODES_SERVICE') ?: 'false',
        'allow_older_lpas' => filter_var(getenv('ALLOW_OLDER_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'allow_meris_lpas' => filter_var(getenv('ALLOW_MERIS_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'save_older_lpa_requests' => filter_var(getenv('SAVE_OLDER_LPA_REQUESTS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'dont_send_lpas_registered_after_sep_2019_to_cleansing_team' => filter_var(
            getenv('DONT_SEND_LPAS_REGISTERED_AFTER_SEP_2019_TO_CLEANSING_TEAM'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,

    ],
];
