<?php

declare(strict_types=1);

namespace Common\Service\Log;

/**
 * Contains definitions for event codes to be attached to logged messages where needed.
 * Generally only needed when doing work on the logs that require filtering of specific logging entries.
 */
class EventCodes
{
    /**
     * An actor user account has been activated
     */
    public const string ACCOUNT_ACTIVATED = 'ACCOUNT_ACTIVATED';

    /**
     * An actor user account has been created
     */
    public const string ACCOUNT_CREATED = 'ACCOUNT_CREATED';

    /**
     * An actor user account has been deleted
     */
    public const string ACCOUNT_DELETED = 'ACCOUNT_DELETED';

    /**
     * User has an activation key
     */
    public const string ACTIVATION_KEY_EXISTS = 'ACTIVATION_KEY_EXISTS';

    /**
     * User does not have an activation key
     */
    public const string ACTIVATION_KEY_NOT_EXISTS = 'ACTIVATION_KEY_NOT_EXISTS';

    /**
     * Activation key request made by replacement attorney
     */
    public const string ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY = 'ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY';

    /**
     * LPA type added is hw
     */
    public const string ADDED_LPA_TYPE_HW = 'ADDED_LPA_TYPE_HW';

    /**
     * LPA type added is pfa
     */
    public const string ADDED_LPA_TYPE_PFA = 'ADDED_LPA_TYPE_PFA';

    /**
     * Request to add an LPA failed as the LPA has already been added
     */
    public const string ADD_LPA_ALREADY_ADDED = 'ADD_LPA_ALREADY_ADDED';

    /**
     * An LPA failed to be added
     */
    public const string ADD_LPA_FAILURE = 'ADD_LPA_FAILURE';

    /**
     * An LPA was found when requesting to add an LPA
     */
    public const string ADD_LPA_FOUND = 'ADD_LPA_FOUND';

    /**
     * Request to add an LPA failed as the LPA is not registered
     */
    public const string ADD_LPA_NOT_ELIGIBLE = 'ADD_LPA_NOT_ELIGIBLE';

    /**
     * Request to add an LPA failed with the details provided
     */
    public const string ADD_LPA_NOT_FOUND = 'ADD_LPA_NOT_FOUND';

    /**
     * An LPA has been added successfully
     */
    public const string ADD_LPA_SUCCESS = 'ADD_LPA_SUCCESS';

    /**
     * GOVUK One Login is returning that an error has occurred
     */
    public const string AUTH_ONELOGIN_ERROR = 'AUTH_ONELOGIN_ERROR';

    /**
     * GOVUK One Login is returning that it is unavailable
     */
    public const string AUTH_ONELOGIN_NOT_AVAILABLE = 'AUTH_ONELOGIN_NOT_AVAILABLE';

    /**
     * GOVUK One login returned successfully but our session is missing
     */
    public const string AUTH_ONELOGIN_MISSING_SESSION = 'AUTH_ONELOGIN_MISSING_SESSION';

    /**
     * Lpa summary has been downloaded
     */
    public const string DOWNLOAD_SUMMARY = 'DOWNLOAD_SUMMARY';

    /**
     * An incoming identity hash has unexpectedly changed
     */
    public const string IDENTITY_HASH_CHANGE = 'IDENTITY_HASH_CHANGE';

    /**
     * An LPA was removed from a users account
     */
    public const string LPA_REMOVED = 'LPA_REMOVED';

    /**
     * Request for an activation key failed as the LPA has already been added
     */
    public const string OLDER_LPA_ALREADY_ADDED = 'OLDER_LPA_ALREADY_ADDED';

    /**
     * LPA does not match
     */
    public const string OLDER_LPA_DOES_NOT_MATCH = 'OLDER_LPA_DOES_NOT_MATCH';

    /**
     * An LPA force requesting another activation key again
     */
    public const string OLDER_LPA_FORCE_ACTIVATION_KEY = 'OLDER_LPA_FORCE_ACTIVATION_KEY';

    /**
     * Older LPA match
     */
    public const string OLDER_LPA_FOUND = 'OLDER_LPA_FOUND';

    /**
     * LPA has activation key
     */
    public const string OLDER_LPA_HAS_ACTIVATION_KEY = 'OLDER_LPA_HAS_ACTIVATION_KEY';

    /**
     * An activation key has already been requested for this LPA but not activated
     */
    public const string OLDER_LPA_KEY_ALREADY_REQUESTED = 'OLDER_LPA_KEY_ALREADY_REQUESTED';

    /**
     * An LPA that is not clean
     */
    public const string OLDER_LPA_NEEDS_CLEANSING = 'OLDER_LPA_NEEDS_CLEANSING';

    /**
     * LPA not eligible
     */
    public const string OLDER_LPA_NOT_ELIGIBLE = 'OLDER_LPA_NOT_ELIGIBLE';

    /**
     * LPA not found
     */
    public const string OLDER_LPA_NOT_FOUND = 'OLDER_LPA_NOT_FOUND';

    /**
     * Older LPA match and letter requested
     */
    public const string OLDER_LPA_SUCCESS = 'OLDER_LPA_SUCCESS';

    /**
     * Activation key requested for an Attorney on an older older LPA
     */
    public const string OOLPA_KEY_REQUESTED_FOR_ATTORNEY = 'OOLPA_KEY_REQUESTED_FOR_ATTORNEY';

    /**
     * Activation key requested for the Donor on an older older LPA
     */
    public const string OOLPA_KEY_REQUESTED_FOR_DONOR = 'OOLPA_KEY_REQUESTED_FOR_DONOR';

    /**
     * A phone number was NOT provided as part of an older older LPA activation key request
     */
    public const string OOLPA_PHONE_NUMBER_NOT_PROVIDED = 'OOLPA_PHONE_NUMBER_NOT_PROVIDED';

    /**
     * A phone number was provided as part of an older older LPA activation key request
     */
    public const string OOLPA_PHONE_NUMBER_PROVIDED = 'OOLPA_PHONE_NUMBER_PROVIDED';

    /**
     * Activation key request is successful with a current address that is abroad
     */
    public const string USER_ABROAD_ADDRESS_REQUEST_SUCCESS = 'USER_ABROAD_ADDRESS_REQUEST_SUCCESS';

    /**
     * A share code has been attempted to be used but was cancelled
     */
    public const string VIEW_LPA_SHARE_CODE_CANCELLED = 'VIEW_LPA_SHARE_CODE_CANCELLED';

    /**
     * A share code has been attempted to be used but had expired
     */
    public const string VIEW_LPA_SHARE_CODE_EXPIRED = 'VIEW_LPA_SHARE_CODE_EXPIRED';

    /**
     * A share code has been attempted and not found
     */
    public const string VIEW_LPA_SHARE_CODE_NOT_FOUND = 'VIEW_LPA_SHARE_CODE_NOT_FOUND';

    /**
     * An LPA has been found using a share code
     */
    public const string VIEW_LPA_SHARE_CODE_SUCCESS = 'VIEW_LPA_SHARE_CODE_SUCCESS';

    /**
     * A paper verification code has been attempted and not found
     */
    public const string VIEW_LPA_PV_CODE_NOT_FOUND = 'VIEW_LPA_PV_CODE_NOT_FOUND';

    /**
     * A paper verification code has been attempted to be used but had expired
     */
    public const string VIEW_LPA_PV_CODE_EXPIRED = 'VIEW_LPA_PV_CODE_EXPIRED';

    /**
     * A paper verification code has been attempted to be used but was cancelled
     */
    public const string VIEW_LPA_PV_CODE_CANCELLED = 'VIEW_LPA_PV_CODE_CANCELLED';

    /**
     * An LPA has been found using a pv code
     */
    public const string VIEW_LPA_PV_CODE_SUCCESS = 'VIEW_LPA_PV_CODE_SUCCESS';
}
