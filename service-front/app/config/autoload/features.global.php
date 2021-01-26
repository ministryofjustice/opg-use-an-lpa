<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey' => filter_var(getenv('USE_OLDER_LPA_JOURNEY'), FILTER_VALIDATE_BOOLEAN) ?: false,
    ]
];
