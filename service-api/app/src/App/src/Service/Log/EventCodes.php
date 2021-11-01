<?php

declare(strict_types=1);

namespace App\Service\Log;

/**
 * Class Event
 *
 * Contains definitions for event codes to be attached to logged messages where needed.
 * Generally only needed when doing work on the logs that require filtering of specific logging entries.
 *
 * @package App\Service\Log
 */
class EventCodes
{
    /**
     * The LPA entered is not marked as 'Registered'
     */
    public const OLDER_LPA_INVALID_STATUS = 'OLDER_LPA_INVALID_STATUS';

    /**
     * The LPA was registered before 1 September 2019
     */
    public const OLDER_LPA_TOO_OLD = 'OLDER_LPA_TOO_OLD';

    /**
     * Older LPA cleanse requested
     */
    public const OLDER_LPA_CLEANSE_SUCCESS = 'OLDER_LPA_CLEANSE_SUCCESS';
}
