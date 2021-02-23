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
    public const SHARE_CODE_NOT_FOUND = 'SHARE_CODE_NOT_FOUND';

    /**
     * LPA not found
     */
    public const LPA_NOT_FOUND = 'LPA_NOT_FOUND';

    /**
     * LPA does not match
     */
    public const LPA_DOES_NOT_MATCH = 'LPA_DOES_NOT_MATCH';

    /**
     * LPA not eligible
     */
    public const LPA_NOT_ELIGIBLE = 'LPA_NOT_ELIGIBLE';

    /**
     * LPA has activation key
     */
    public const LPA_HAS_ACTIVATION_KEY = 'LPA_HAS_ACTIVATION_KEY';

    /**
     * LPA has activation key created in the last 14 days
     */
    public const LPA_HAS_ACTIVATION_KEY_WITHIN_14_DAYS = 'LPA_HAS_ACTIVATION_KEY_WITHIN_14_DAYS';
}
