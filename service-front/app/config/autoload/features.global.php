<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey' => filter_var(getenv('USE_OLDER_LPA_JOURNEY'), FILTER_VALIDATE_BOOLEAN) ?: false,
        'delete_lpa_feature' => filter_var(getenv('DELETE_LPA_FEATURE'), FILTER_VALIDATE_BOOLEAN) ?: false,
    ]
];
