<?php

declare(strict_types=1);

namespace Common\Service\Log;

/**
 * Class ErrorCodes
 *
 * Contains definitions for error codes to be attached to logged messages where needed.
 * Generally only needed when doing work on the logs that require filtering of specific logging entries.
 *
 * @package Common\Service\Log
 */
class ErrorCodes
{
    /**
     * A share code has been attempted and not found
     */
    public const SHARE_CODE_NOT_FOUND = 'SHARE_CODE_NOT_FOUND';

    /**
     * An incoming identity hash has unexpectedly changed
     */
    public const IDENTITY_HASH_CHANGE = 'IDENTITY_HASH_CHANGE';

}