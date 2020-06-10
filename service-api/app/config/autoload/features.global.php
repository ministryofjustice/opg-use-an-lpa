<?php

declare(strict_types=1);

return [
    'feature_flags' => [
        'use_legacy_codes_service' => getenv('USE_LEGACY_CODES_SERVICE') ?: 'false',
    ]
];
