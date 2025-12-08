<?php

declare(strict_types=1);

namespace App\Service\Log;

/**
 * Contains definitions for event codes to be attached to logged messages where needed.
 * Generally only needed when doing work on the logs that require filtering of specific logging entries.
 */
class EventCodes
{
    /**
     * The LPA entered is not marked as 'Registered'
     */
    public const string OLDER_LPA_INVALID_STATUS = 'OLDER_LPA_INVALID_STATUS';

    /**
     * The LPA was registered before 1 September 2019
     */
    public const string OLDER_LPA_TOO_OLD = 'OLDER_LPA_TOO_OLD';

    /**
     * Unexpected Data LPA API Response
     */
    public const string UNEXPECTED_DATA_LPA_API_RESPONSE = 'UNEXPECTED_DATA_LPA_API_RESPONSE';

    public const string OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED = 'OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED';

    public const string OLDER_LPA_PARTIAL_MATCH_TOO_RECENT = 'OLDER_LPA_PARTIAL_MATCH_TOO_RECENT';

    /**
     * Activation key request is successful for full match LPA type hw
     */
    public const string FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW = 'FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW';

    /**
     * Activation key request is successful for full match LPA type pfa
     */
    public const string FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA = 'FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA';

    /**
     * Activation key request is successful for partial match LPA type hw
     */
    public const string PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW = 'PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW';

    /**
     * Activation key request is successful for partial match LPA type pfa
     */
    public const string PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA = 'PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA';

    /**
     * A one login authentication transaction resulted in a username/password account being migrated.
     */
    public const string AUTH_ONELOGIN_ACCOUNT_MIGRATED = 'AUTH_ONELOGIN_ACCOUNT_MIGRATED';

    /**
     * A one login authentication transaction resulted in a new local account being created.
     */
    public const string AUTH_ONELOGIN_ACCOUNT_CREATED = 'AUTH_ONELOGIN_ACCOUNT_CREATED';

    /**
     * A one login authentication transaction resulted in a local account being recovered during an email change.
     */
    public const string AUTH_ONELOGIN_ACCOUNT_RECOVERED = 'AUTH_ONELOGIN_ACCOUNT_RECOVERED';

    /**
     * Whilst retrieving an LPA from upstream sources, using information that refers to it,
     * the LPA was not found.
     */
    public const string EXPECTED_LPA_MISSING = 'EXPECTED_LPA_MISSING';

    /**
     * Record the organisation name viewing the LPA during the Paper Verification Journey
     */
    public const string PAPER_VERIFICATION_CODE_ORGANISATION_VIEW = 'PAPER_VERIFICATION_CODE_ORGANISATION_VIEW';

    /**
     * A paper verification code has been used for the first time and the expiry period started
     */
    public const string PAPER_VERIFICATION_CODE_FIRST_TIME_USE = 'PAPER_VERIFICATION_CODE_FIRST_TIME_USE';

    /**
     * A paper channel actor with has used the LPA online for the first time and has started the 30
     * day migration to being online only.
     */
    public const string PAPER_VERIFICATION_CODE_PAPER_TO_DIGITAL_TRANSITION = 'PAPER_TO_DIGITAL_TRANSITION';
}
