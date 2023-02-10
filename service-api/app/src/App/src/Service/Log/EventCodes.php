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

    /**
     * Unexpected Data LPA API Response
     */
    public const UNEXPECTED_DATA_LPA_API_RESPONSE = 'UNEXPECTED_DATA_LPA_API_RESPONSE';

    public const OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED = 'OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED';

    public const OLDER_LPA_PARTIAL_MATCH_TOO_RECENT = 'OLDER_LPA_PARTIAL_MATCH_TOO_RECENT';

    /**
     * Activation key request is successful for full match LPA type hw
     */
    public const FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW = 'FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW';

    /**
     * Activation key request is successful for full match LPA type hw
     */
    public const FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PF = 'FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PF';

    /**
     * Activation key request is successful for partial match LPA type hw
     */
    public const PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW = 'PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW';

    /**
     * Activation key request is successful for partial match LPA type hw
     */
    public const PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PF = 'PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PF';
}
