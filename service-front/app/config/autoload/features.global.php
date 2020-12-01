<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_older_lpa_journey' => getenv('USE_OLDER_LPA_JOURNEY') ?: false,
    ]
];
