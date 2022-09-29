<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey'                                      => filter_var(getenv('USE_OLDER_LPA_JOURNEY'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'delete_lpa_feature'                                         => filter_var(getenv('DELETE_LPA_FEATURE'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'allow_older_lpas'                                           => filter_var(getenv('ALLOW_OLDER_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'allow_meris_lpas'                                           => filter_var(getenv('ALLOW_MERIS_LPAS'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'dont_send_lpas_registered_after_sep_2019_to_cleansing_team' => filter_var(
            getenv('DONT_SEND_LPAS_REGISTERED_AFTER_SEP_2019_TO_CLEANSING_TEAM'),
            FILTER_VALIDATE_BOOLEAN
        ) ?: false,
    ],
];
