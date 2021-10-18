<?php

declare(strict_types=1);

namespace Common\Service\Log;

/**
 * Class Event
 *
 * Contains definitions for event codes to be attached to logged messages where needed.
 * Generally only needed when doing work on the logs that require filtering of specific logging entries.
 *
 * @package Common\Service\Log
 */
class EventCodes
{
    /**
     * An actor user account has been activated
     */
    public const ACCOUNT_ACTIVATED = 'ACCOUNT_ACTIVATED';

    /**
     * An actor user account has been created
     */
    public const ACCOUNT_CREATED = 'ACCOUNT_CREATED';

    /**
     * An actor user account has been deleted
     */
    public const ACCOUNT_DELETED = 'ACCOUNT_DELETED';

    /**
     * An incoming identity hash has unexpectedly changed
     */
    public const IDENTITY_HASH_CHANGE = 'IDENTITY_HASH_CHANGE';

    /**
     * A share code has been attempted and not found
     */
    public const VIEW_LPA_SHARE_CODE_NOT_FOUND = 'VIEW_LPA_SHARE_CODE_NOT_FOUND';

    /**
     * A share code has been attempted to be used but was cancelled
     */
    public const VIEW_LPA_SHARE_CODE_CANCELLED = 'VIEW_LPA_SHARE_CODE_CANCELLED';

    /**
     * A share code has been attempted to be used but had expired
     */
    public const VIEW_LPA_SHARE_CODE_EXPIRED = 'VIEW_LPA_SHARE_CODE_EXPIRED';

    /**
     * An LPA has been found using a share code
     */
    public const VIEW_LPA_SHARE_CODE_SUCCESS = 'VIEW_LPA_SHARE_CODE_SUCCESS';

    /**
     * Request for an activation key failed as the LPA has already been added
     */
    public const OLDER_LPA_ALREADY_ADDED = 'OLDER_LPA_ALREADY_ADDED';

    /**
     * LPA not found
     */
    public const OLDER_LPA_NOT_FOUND = 'OLDER_LPA_NOT_FOUND';

    /**
     * LPA does not match
     */
    public const OLDER_LPA_DOES_NOT_MATCH = 'OLDER_LPA_DOES_NOT_MATCH';

    /**
     * LPA not eligible
     */
    public const OLDER_LPA_NOT_ELIGIBLE = 'OLDER_LPA_NOT_ELIGIBLE';

    /**
     * LPA has activation key
     */
    public const OLDER_LPA_HAS_ACTIVATION_KEY = 'OLDER_LPA_HAS_ACTIVATION_KEY';

    /**
     * Activation key requested for the Donor on an older older LPA
     */
    public const OOLPA_KEY_REQUESTED_FOR_DONOR = 'OOLPA_KEY_REQUESTED_FOR_DONOR';

    /**
     * Activation key requested for an Attorney on an older older LPA
     */
    public const OOLPA_KEY_REQUESTED_FOR_ATTORNEY = 'OOLPA_KEY_REQUESTED_FOR_ATTORNEY';

    /**
     * A phone number was provided as part of an older older LPA activation key request
     */
    public const OOLPA_PHONE_NUMBER_PROVIDED = 'OOLPA_PHONE_NUMBER_PROVIDED';

    /**
     * A phone number was NOT provided as part of an older older LPA activation key request
     */
    public const OOLPA_PHONE_NUMBER_NOT_PROVIDED = 'OOLPA_PHONE_NUMBER_NOT_PROVIDED';

    /**
     * Older LPA match
     */
    public const OLDER_LPA_FOUND = 'OLDER_LPA_FOUND';

    /**
     * Older LPA match and letter requested
     */
    public const OLDER_LPA_SUCCESS = 'OLDER_LPA_SUCCESS';

    /**
     * An LPA force requesting another activation key again
     */
    public const OLDER_LPA_FORCE_ACTIVATION_KEY = 'OLDER_LPA_FORCE_ACTIVATION_KEY';

    /**
     * An activation key has already been requested for this LPA but not activated
     */
    public const OLDER_LPA_KEY_ALREADY_REQUESTED = 'OLDER_LPA_KEY_ALREADY_REQUESTED';

    /**
     * Lpa summary has been downloaded
     */
    public const DOWNLOAD_SUMMARY = 'DOWNLOAD_SUMMARY';

    /**
     * An LPA was found when requesting to add an LPA
     */
    public const ADD_LPA_FOUND = 'ADD_LPA_FOUND';

    /**
     * Request to add an LPA failed with the details provided
     */
    public const ADD_LPA_NOT_FOUND = 'ADD_LPA_NOT_FOUND';

    /**
     * Request to add an LPA failed as the LPA is not registered
     */
    public const ADD_LPA_NOT_ELIGIBLE = 'ADD_LPA_NOT_ELIGIBLE';

    /**
     * Request to add an LPA failed as the LPA has already been added
     */
    public const ADD_LPA_ALREADY_ADDED = 'ADD_LPA_ALREADY_ADDED';

    /**
     * An LPA has been added successfully
     */
    public const ADD_LPA_SUCCESS = 'ADD_LPA_SUCCESS';

    /**
     * An LPA failed to be added
     */
    public const ADD_LPA_FAILURE = 'ADD_LPA_FAILURE';

    /**
     * An LPA was removed from a users account
     */
    public const LPA_REMOVED = 'LPA_REMOVED';
}
