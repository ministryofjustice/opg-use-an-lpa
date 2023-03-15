<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

enum AccessForAllResult: string {
    case SUCCESS                   = 'SUCCESS';
    case FOUND                     = 'FOUND';
    case NOT_FOUND                 = 'NOT_FOUND';
    case NOT_ELIGIBLE              = 'NOT_ELIGIBLE';
    case DOES_NOT_MATCH            = 'NOT_MATCH';
    case HAS_ACTIVATION_KEY        = 'HAS_ACTIVATION_KEY';
    case LPA_ALREADY_ADDED         = 'LPA_ALREADY_ADDED';
    case OLDER_LPA_NEEDS_CLEANSING = 'OLDER_LPA_NEEDS_CLEANSING';
    case KEY_ALREADY_REQUESTED     = 'KEY_ALREADY_REQUESTED';
    case POSTCODE_NOT_SUPPLIED     = 'POSTCODE_NOT_SUPPLIED';
}
